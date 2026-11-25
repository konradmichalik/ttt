<?php

declare(strict_types=1);

/*
 * This file is part of the "ttt" Composer package.
 *
 * (c) Konrad Michalik <hej@konradmichalik.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KonradMichalik\Ttt\Factory;

use Faker\{Factory as FakerFactory, Generator};
use KonradMichalik\Ttt\Runtime\RunningTestCase;
use LogicException;
use Throwable;

use function array_map;
use function count;
use function crc32;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function str_contains;

/**
 * RecordFactory.
 *
 * Base class for record factories. Defaults for every TCA column are derived
 * automatically, definition() only adds what is specific to the record.
 * Instances are immutable: every fluent method returns a modified copy.
 *
 * Requires typo3/cms-core and fakerphp/faker. The faker is seeded from the
 * running test, so a failing test fails the same way on every run.
 *
 * <code>
 * $news = NewsFactory::new()->published()->count(3)
 *     ->for(PageFactory::new(), 'pid')
 *     ->has(CategoryFactory::new()->count(2), 'categories')
 *     ->create();
 * </code>
 *
 * @phpstan-consistent-constructor
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
abstract class RecordFactory
{
    protected string $table = '';
    /** @var array<string, Generator> */
    private static array $generators = [];

    private static ?string $generatorsFor = null;

    private int $count = 1;

    /** @var list<array<string, mixed>> */
    private array $states = [];

    /** @var list<array{RecordFactory, string}> */
    private array $parents = [];

    /** @var list<array{RecordFactory, string}> */
    private array $children = [];

    /** @var list<array{int, array<string, mixed>}> */
    private array $translations = [];

    final public static function new(): static
    {
        return new static();
    }

    final public function count(int $count): static
    {
        $clone = clone $this;
        $clone->count = $count;

        return $clone;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    final public function state(array $attributes): static
    {
        $clone = clone $this;
        $clone->states[] = $attributes;

        return $clone;
    }

    /**
     * Creates the parent first and stores its uid in the given field (for example "pid").
     * All records of one create() run share that parent.
     */
    final public function for(self $parent, string $field): static
    {
        $clone = clone $this;
        $clone->parents[] = [$parent, $field];

        return $clone;
    }

    /**
     * Creates the children after the record and wires them up according to the TCA of the field.
     */
    final public function has(self $child, string $field): static
    {
        $clone = clone $this;
        $clone->children[] = [$child, $field];

        return $clone;
    }

    /**
     * Additionally creates an overlay record in the given language for every record.
     *
     * @param array<string, mixed> $overrides
     */
    final public function translated(int $languageUid, array $overrides = []): static
    {
        $clone = clone $this;
        $clone->translations[] = [$languageUid, $overrides];

        return $clone;
    }

    /**
     * Builds the rows without persisting them and without resolving relations.
     *
     * @return list<array<string, mixed>>
     */
    final public function make(): array
    {
        $faker = $this->faker();
        $rows = [];

        for ($index = 0; $index < $this->count; ++$index) {
            $row = [...TcaDefaults::for($this->table(), $faker), ...$this->definition($faker)];

            foreach ($this->states as $state) {
                $row = [...$row, ...$state];
            }

            $rows[] = $row;
        }

        return $rows;
    }

    final public function create(): RecordCollection
    {
        $log = new CreationLog();

        try {
            return new RecordCollection($this->persist($log), $log);
        } catch (Throwable $throwable) {
            $log->deleteAll();

            throw $throwable;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function definition(Generator $faker): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return list<Record>
     */
    private function persist(CreationLog $log, array $overrides = []): array
    {
        $records = [];

        // Once per run, so that count(3) children share one parent.
        $parentUids = [];

        foreach ($this->parents as [$parent, $field]) {
            $parentUids[$field] = $parent->persist($log)[0]->uid ?? throw new LogicException(sprintf('The parent factory for "%s" created no record.', $field), 1753900704);
        }

        foreach ($this->make() as $row) {
            $row = [...$row, ...$overrides, ...$parentUids];

            $uid = $this->insert($row, $log);
            $translations = [];
            $base = $row;

            foreach ($this->children as [$child, $field]) {
                $row = [...$row, ...$this->attachChildren($uid, $field, $child, $log)];
            }

            foreach ($this->translations as [$languageUid, $translationOverrides]) {
                $translations[] = $this->createTranslation($base, $uid, $languageUid, $translationOverrides, $log);
            }

            $records[] = new Record($this->table(), $uid, $row, $translations);
        }

        return $records;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insert(array $row, CreationLog $log): int
    {
        $uid = RecordPersister::insert($this->table(), $row);
        $log->recordInserted($this->table(), $uid);

        return $uid;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $overrides
     */
    private function createTranslation(array $row, int $parentUid, int $languageUid, array $overrides, CreationLog $log): Record
    {
        $ctrl = $GLOBALS['TCA'][$this->table()]['ctrl'] ?? [];
        $languageField = is_array($ctrl) ? ($ctrl['languageField'] ?? null) : null;
        $pointerField = is_array($ctrl) ? ($ctrl['transOrigPointerField'] ?? null) : null;

        if (!is_string($languageField) || !is_string($pointerField)) {
            throw new LogicException(sprintf('Table "%s" is not translatable: its TCA ctrl has no languageField and transOrigPointerField.', $this->table()), 1753900705);
        }

        $translation = [...$row, ...$overrides, $languageField => $languageUid, $pointerField => $parentUid];
        $uid = $this->insert($translation, $log);

        return new Record($this->table(), $uid, $translation);
    }

    /**
     * Wires the children to the record and returns the changed columns of the record.
     *
     * @return array<string, int|string>
     */
    private function attachChildren(int $uid, string $field, self $child, CreationLog $log): array
    {
        $config = $GLOBALS['TCA'][$this->table()]['columns'][$field]['config'] ?? null;

        if (!is_array($config)) {
            throw new LogicException(sprintf('Field "%s" of table "%s" has no TCA configuration, so the relation cannot be resolved.', $field, $this->table()), 1753900706);
        }

        $type = is_string($config['type'] ?? null) ? $config['type'] : '';

        if (is_string($config['MM'] ?? null)) {
            return $this->attachViaMm($uid, $field, $config['MM'], $config, $child, $log);
        }

        $this->assertForeignTable($config, $child, $field);

        if ('inline' === $type && is_string($config['foreign_field'] ?? null)) {
            $matchFields = is_array($config['foreign_match_fields'] ?? null) ? $config['foreign_match_fields'] : [];
            $overrides = [...$matchFields, $config['foreign_field'] => $uid];

            if (is_string($config['foreign_table_field'] ?? null)) {
                $overrides[$config['foreign_table_field']] = $this->table();
            }

            $children = $child->persist($log, $overrides);
            $this->sortChildren($config, $children);

            return $this->updateField($uid, $field, count($children));
        }

        if (in_array($type, ['select', 'group', 'inline', 'category'], true)) {
            $children = $child->persist($log);
            // A group over several tables needs "table_uid", a bare uid would point to the first table.
            $prefixed = 'group' === $type && is_string($config['allowed'] ?? null) && str_contains($config['allowed'], ',');

            return $this->updateField($uid, $field, implode(',', array_map(static fn (Record $record): int|string => $prefixed ? $record->table.'_'.$record->uid : $record->uid, $children)));
        }

        throw new LogicException(sprintf('Field "%s" of table "%s" has the type "%s", which cannot hold child records.', $field, $this->table(), $type), 1753900707);
    }

    /**
     * @param array<array-key, mixed> $config
     *
     * @return array<string, int|string>
     */
    private function attachViaMm(int $uid, string $field, string $mmTable, array $config, self $child, CreationLog $log): array
    {
        $this->assertForeignTable($config, $child, $field);

        $children = $child->persist($log);
        $isOppositeSide = 'category' === ($config['type'] ?? null) || isset($config['MM_opposite_field']);
        $matchFields = is_array($config['MM_match_fields'] ?? null) ? $config['MM_match_fields'] : [];

        foreach ($children as $index => $record) {
            $row = [
                'uid_local' => $isOppositeSide ? $record->uid : $uid,
                'uid_foreign' => $isOppositeSide ? $uid : $record->uid,
                $isOppositeSide ? 'sorting_foreign' : 'sorting' => $index + 1,
            ];

            if ('category' === ($config['type'] ?? null)) {
                $row['tablenames'] = $this->table();
                $row['fieldname'] = $field;
            }

            foreach ($matchFields as $column => $value) {
                if (is_string($column) && (is_string($value) || is_int($value))) {
                    $row[$column] = $value;
                }
            }

            RecordPersister::insertWithoutUid($mmTable, $row);
            $log->mmRowInserted($mmTable, $row);
        }

        return $this->updateField($uid, $field, count($children));
    }

    /**
     * @param array<array-key, mixed> $config
     * @param list<Record>            $children
     */
    private function sortChildren(array $config, array $children): void
    {
        if (!is_string($config['foreign_sortby'] ?? null)) {
            return;
        }

        foreach ($children as $index => $record) {
            RecordPersister::update($record->table, $record->uid, [$config['foreign_sortby'] => $index + 1]);
        }
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private function assertForeignTable(array $config, self $child, string $field): void
    {
        $foreignTable = $config['foreign_table'] ?? null;

        if (is_string($foreignTable) && $foreignTable !== $child->table()) {
            throw new LogicException(sprintf('Field "%s" of table "%s" points to "%s", but the child factory creates "%s" records.', $field, $this->table(), $foreignTable, $child->table()), 1753900708);
        }
    }

    /**
     * @return array<string, int|string>
     */
    private function updateField(int $uid, string $field, int|string $value): array
    {
        RecordPersister::update($this->table(), $uid, [$field => $value]);

        return [$field => $value];
    }

    private function table(): string
    {
        if ('' === $this->table) {
            throw new LogicException(sprintf('%s must set the $table property.', static::class), 1753900709);
        }

        return $this->table;
    }

    private function faker(): Generator
    {
        $test = RunningTestCase::find();
        // Defensive: factories are used while a test runs, outside of one the seed is just fixed.
        // @codeCoverageIgnoreStart
        $testId = null === $test ? '' : $test::class.'::'.$test->name().'#'.$test->dataName();
        // @codeCoverageIgnoreEnd

        // One generator per test and table, shared by all runs: separate runs must not repeat each other's values.
        if (self::$generatorsFor !== $testId) {
            self::$generators = [];
            self::$generatorsFor = $testId;
        }

        if (!isset(self::$generators[$this->table])) {
            $faker = FakerFactory::create();
            $faker->seed(crc32($testId.'|'.$this->table));
            self::$generators[$this->table] = $faker;
        }

        return self::$generators[$this->table];
    }
}

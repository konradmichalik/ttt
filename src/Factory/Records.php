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

use LogicException;

use function array_merge;
use function array_values;
use function sprintf;

/**
 * Records.
 *
 * Access to the records that #[WithRecords] created for the running test.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class Records
{
    /** @var array<class-string<RecordFactory>, list<RecordCollection>> */
    private static array $created = [];

    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    /**
     * @param class-string<RecordFactory> $factory
     */
    public static function of(string $factory): RecordCollection
    {
        $collections = self::$created[$factory] ?? [];

        if ([] === $collections) {
            throw new LogicException(sprintf('No records of %s were created for this test. Add #[WithRecords(%s::class)] to the test.', $factory, $factory), 1753900710);
        }

        return new RecordCollection(array_merge(...array_map(static fn (RecordCollection $collection): array => $collection->all(), $collections)));
    }

    /**
     * @param class-string<RecordFactory> $factory
     *
     * @internal
     */
    public static function register(string $factory, RecordCollection $collection): void
    {
        self::$created[$factory][] = $collection;
    }

    /**
     * @param class-string<RecordFactory> $factory
     *
     * @internal
     */
    public static function unregister(string $factory, RecordCollection $collection): void
    {
        self::$created[$factory] = array_values(array_filter(
            self::$created[$factory] ?? [],
            static fn (RecordCollection $registered): bool => $registered !== $collection,
        ));

        if ([] === self::$created[$factory]) {
            unset(self::$created[$factory]);
        }
    }
}

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

use Faker\Generator;
use LogicException;

use function array_key_exists;
use function ceil;
use function floor;
use function gmdate;
use function is_array;
use function is_float;
use function is_int;
use function is_scalar;
use function is_string;
use function max;
use function min;
use function sprintf;
use function str_contains;
use function substr;
use function time;

/**
 * TcaDefaults.
 *
 * Derives a valid default value for every TCA column of a table, so a
 * factory only has to spell out what the test cares about. Text types get
 * words, datetime a date, check 0, select and radio their first item,
 * number a value inside its range. Relations stay empty unless set
 * explicitly. Content Blocks are covered, they generate regular TCA.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class TcaDefaults
{
    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    /**
     * @return array<string, mixed>
     */
    public static function for(string $table, Generator $faker): array
    {
        $tca = $GLOBALS['TCA'][$table] ?? null;

        if (!is_array($tca)) {
            throw new LogicException(sprintf('No TCA found for table "%s". Load the extension that defines it before creating records.', $table), 1753900703);
        }

        $row = ['pid' => 0];
        $ctrl = is_array($tca['ctrl'] ?? null) ? $tca['ctrl'] : [];

        foreach (['crdate', 'tstamp'] as $key) {
            if (is_string($ctrl[$key] ?? null)) {
                $row[$ctrl[$key]] = $GLOBALS['EXEC_TIME'] ?? time();
            }
        }

        $columns = is_array($tca['columns'] ?? null) ? $tca['columns'] : [];

        foreach ($columns as $name => $column) {
            $config = is_array($column) && is_array($column['config'] ?? null) ? $column['config'] : [];
            $value = self::valueFor($config, $faker);

            if (null !== $value) {
                $row[(string) $name] = $value;
            }
        }

        return $row;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function valueFor(array $config, Generator $faker): string|int|float|null
    {
        if (array_key_exists('default', $config) && is_scalar($config['default'])) {
            return is_string($config['default']) || is_int($config['default']) || is_float($config['default']) ? $config['default'] : (int) $config['default'];
        }

        return match ($config['type'] ?? null) {
            'input' => self::input($config, $faker),
            'text' => $faker->sentence(),
            'email' => $faker->safeEmail(),
            'link' => $faker->url(),
            'slug' => '/'.$faker->slug(),
            'check' => 0,
            'number' => self::number($config, $faker),
            'datetime' => self::datetime($config, $faker),
            'select', 'radio' => self::firstItem($config),
            default => null,
        };
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function input(array $config, Generator $faker): string|int
    {
        $eval = is_string($config['eval'] ?? null) ? $config['eval'] : '';

        if (str_contains($eval, 'int')) {
            return $faker->numberBetween(0, 100);
        }

        $value = $faker->words(3, true);
        $value = is_string($value) ? $value : '';
        $max = $config['max'] ?? null;

        return is_int($max) && $max > 0 ? substr($value, 0, $max) : $value;
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function number(array $config, Generator $faker): int|float
    {
        $range = is_array($config['range'] ?? null) ? $config['range'] : [];
        $lower = is_int($range['lower'] ?? null) || is_float($range['lower'] ?? null) ? $range['lower'] : 0;
        $upper = is_int($range['upper'] ?? null) || is_float($range['upper'] ?? null) ? $range['upper'] : max($lower, 100);
        $min = min($lower, $upper);
        $max = max($lower, $upper);

        if ('decimal' === ($config['format'] ?? null)) {
            return $faker->randomFloat(2, $min, $max);
        }

        return $faker->numberBetween((int) ceil($min), (int) floor($max));
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function datetime(array $config, Generator $faker): string|int
    {
        // A fixed window instead of "now": the same seed has to give the same value on every run.
        $timestamp = $faker->numberBetween(1_577_836_800, 1_893_456_000);

        return match ($config['dbType'] ?? null) {
            'date' => gmdate('Y-m-d', $timestamp),
            'datetime' => gmdate('Y-m-d H:i:s', $timestamp),
            'time' => gmdate('H:i:s', $timestamp),
            default => $timestamp,
        };
    }

    /**
     * @param array<array-key, mixed> $config
     */
    private static function firstItem(array $config): string|int|null
    {
        if (isset($config['foreign_table']) || isset($config['MM'])) {
            return null;
        }

        foreach (is_array($config['items'] ?? null) ? $config['items'] : [] as $item) {
            $value = is_array($item) ? ($item['value'] ?? $item[1] ?? null) : null;

            if ((is_string($value) || is_int($value)) && '--div--' !== $value) {
                return $value;
            }
        }

        return null;
    }
}

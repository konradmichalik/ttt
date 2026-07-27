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

namespace KonradMichalik\Ttt\Assertion;

use PHPUnit\Framework\Assert;

use function array_key_exists;
use function explode;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;

/**
 * JsonAssertions.
 *
 * Dot-path based assertions for JSON strings or already decoded arrays,
 * e.g. assertJsonPath($response, 'result.items.0.uid', 42). Intended for
 * MCP responses, OpenAPI output and machine-readable artifacts.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
trait JsonAssertions
{
    /**
     * @param string|array<array-key, mixed> $json
     */
    public static function assertJsonPath(string|array $json, string $path, mixed $expected): void
    {
        ['found' => $found, 'value' => $value, 'missingSegment' => $missingSegment] = self::traverseJsonPath($json, $path);

        if (!$found) {
            Assert::fail(sprintf('JSON path "%s" does not exist (missing segment "%s").', $path, $missingSegment));
        }

        Assert::assertSame($expected, $value, sprintf('Failed asserting JSON path "%s".', $path));
    }

    /**
     * @param string|array<array-key, mixed> $json
     */
    public static function assertJsonHasPath(string|array $json, string $path): void
    {
        ['found' => $found, 'missingSegment' => $missingSegment] = self::traverseJsonPath($json, $path);

        Assert::assertTrue($found, sprintf('JSON path "%s" does not exist (missing segment "%s").', $path, $missingSegment));
    }

    /**
     * @param string|array<array-key, mixed> $json
     * @param list<string>                   $paths
     */
    public static function assertJsonHasPaths(string|array $json, array $paths): void
    {
        foreach ($paths as $path) {
            self::assertJsonHasPath($json, $path);
        }
    }

    /**
     * @param string|array<array-key, mixed> $json
     */
    public static function assertJsonMissingPath(string|array $json, string $path): void
    {
        ['found' => $found] = self::traverseJsonPath($json, $path);

        Assert::assertFalse($found, sprintf('JSON path "%s" unexpectedly exists.', $path));
    }

    /**
     * @param string|array<array-key, mixed> $json
     * @param list<string>                   $paths
     */
    public static function assertJsonMissingPaths(string|array $json, array $paths): void
    {
        foreach ($paths as $path) {
            self::assertJsonMissingPath($json, $path);
        }
    }

    /**
     * @param string|array<array-key, mixed> $json
     *
     * @return array{found: bool, value: mixed, missingSegment: string|null}
     */
    private static function traverseJsonPath(string|array $json, string $path): array
    {
        $data = is_string($json)
            ? json_decode($json, true, 512, \JSON_THROW_ON_ERROR)
            : $json;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return ['found' => false, 'value' => null, 'missingSegment' => $segment];
            }

            $data = $data[$segment];
        }

        return ['found' => true, 'value' => $data, 'missingSegment' => null];
    }
}

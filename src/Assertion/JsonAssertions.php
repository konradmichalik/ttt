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
        Assert::assertSame($expected, self::resolveJsonPath($json, $path), sprintf('Failed asserting JSON path "%s".', $path));
    }

    /**
     * @param string|array<array-key, mixed> $json
     */
    public static function assertJsonHasPath(string|array $json, string $path): void
    {
        self::resolveJsonPath($json, $path);
        Assert::assertTrue(true);
    }

    /**
     * @param string|array<array-key, mixed>  $json
     * @param list<string>                    $paths
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
    private static function resolveJsonPath(string|array $json, string $path): mixed
    {
        $data = is_string($json)
            ? json_decode($json, true, 512, \JSON_THROW_ON_ERROR)
            : $json;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($data) || !\array_key_exists($segment, $data)) {
                Assert::fail(sprintf('JSON path "%s" does not exist (missing segment "%s").', $path, $segment));
            }

            $data = $data[$segment];
        }

        return $data;
    }
}

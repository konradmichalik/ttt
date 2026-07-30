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

namespace KonradMichalik\Ttt\Handler;

use KonradMichalik\Ttt\Attribute\Typo3ConfVarsSentinel;

use function is_array;

/**
 * SentinelAwareArrayMerge.
 *
 * Deep merge shared by handlers that merge a configuration array onto an
 * existing one and need Typo3ConfVarsSentinel::Unset to explicitly clear a
 * key the merge would otherwise leave untouched (an override of `[]` is a
 * no-op against an existing array subtree).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class SentinelAwareArrayMerge
{
    private function __construct() {}

    /**
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> $override
     *
     * @return array<array-key, mixed>
     */
    public static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (Typo3ConfVarsSentinel::Unset === $value) {
                unset($base[$key]);
                continue;
            }

            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = self::merge($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}

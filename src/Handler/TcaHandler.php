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

use Closure;
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithTca};

use function array_key_exists;
use function assert;
use function is_array;

/**
 * TcaHandler.
 *
 * Snapshots $GLOBALS['TCA'][$table], deep-merges the attribute configuration
 * and restores the exact previous state (including a previously absent
 * table) via the returned restorer.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class TcaHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithTca;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithTca);

        $table = $attribute->table;
        $tca = array_key_exists('TCA', $GLOBALS) && is_array($GLOBALS['TCA']) ? $GLOBALS['TCA'] : [];
        $existed = array_key_exists($table, $tca) && is_array($tca[$table]);
        /** @var array<string, mixed> $snapshot */
        $snapshot = $existed ? $tca[$table] : [];

        $GLOBALS['TCA'][$table] = SentinelAwareArrayMerge::merge($snapshot, $attribute->configuration);

        return static function () use ($table, $existed, $snapshot): void {
            if ($existed) {
                $GLOBALS['TCA'][$table] = $snapshot;
            } else {
                unset($GLOBALS['TCA'][$table]);
            }
        };
    }
}

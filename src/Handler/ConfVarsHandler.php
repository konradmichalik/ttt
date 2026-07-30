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
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithTypo3ConfVars};

use function array_key_exists;
use function assert;
use function is_array;

/**
 * ConfVarsHandler.
 *
 * Snapshots $GLOBALS['TYPO3_CONF_VARS'], deep-merges the attribute
 * configuration and restores the exact previous state (including a previously
 * unset global) via the returned restorer.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ConfVarsHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithTypo3ConfVars;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithTypo3ConfVars);

        $existed = array_key_exists('TYPO3_CONF_VARS', $GLOBALS) && is_array($GLOBALS['TYPO3_CONF_VARS']);
        /** @var array<string, mixed> $snapshot */
        $snapshot = $existed ? $GLOBALS['TYPO3_CONF_VARS'] : [];

        $GLOBALS['TYPO3_CONF_VARS'] = SentinelAwareArrayMerge::merge($snapshot, $attribute->configuration);

        return static function () use ($existed, $snapshot): void {
            if ($existed) {
                $GLOBALS['TYPO3_CONF_VARS'] = $snapshot;
            } else {
                unset($GLOBALS['TYPO3_CONF_VARS']);
            }
        };
    }
}

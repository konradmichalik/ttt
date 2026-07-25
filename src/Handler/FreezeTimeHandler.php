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
use DateTimeImmutable;
use KonradMichalik\Ttt\Attribute\{FreezeTime, TttAttribute};
use TYPO3\CMS\Core\Context\{Context, DateTimeAspect};
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function assert;

/**
 * FreezeTimeHandler.
 *
 * Applies FreezeTime: pins the Context date aspect and the legacy execution
 * time globals to a fixed timestamp and restores everything afterwards -
 * including the singleton map, in case the Context singleton did not exist
 * before.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class FreezeTimeHandler implements AttributeHandler
{
    private const TIME_GLOBALS = ['EXEC_TIME', 'SIM_EXEC_TIME', 'ACCESS_TIME', 'SIM_ACCESS_TIME'];

    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof FreezeTime;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof FreezeTime);

        $singletonSnapshot = GeneralUtility::getSingletonInstances();
        $globalsSnapshot = [];

        foreach (self::TIME_GLOBALS as $name) {
            $globalsSnapshot[$name] = array_key_exists($name, $GLOBALS) ? $GLOBALS[$name] : null;
        }

        $frozen = new DateTimeImmutable($attribute->dateTime);
        $timestamp = $frozen->getTimestamp();

        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('date', new DateTimeAspect($frozen));

        $GLOBALS['EXEC_TIME'] = $timestamp;
        $GLOBALS['SIM_EXEC_TIME'] = $timestamp;
        $GLOBALS['ACCESS_TIME'] = $timestamp - ($timestamp % 60);
        $GLOBALS['SIM_ACCESS_TIME'] = $timestamp - ($timestamp % 60);

        return static function () use ($singletonSnapshot, $globalsSnapshot): void {
            foreach ($globalsSnapshot as $name => $value) {
                if (null === $value) {
                    unset($GLOBALS[$name]);
                } else {
                    $GLOBALS[$name] = $value;
                }
            }

            GeneralUtility::resetSingletonInstances($singletonSnapshot);
        };
    }
}

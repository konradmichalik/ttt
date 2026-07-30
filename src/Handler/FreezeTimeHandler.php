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
use ReflectionProperty;
use TYPO3\CMS\Core\Context\{AspectInterface, Context, DateTimeAspect};
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function assert;

/**
 * FreezeTimeHandler.
 *
 * Applies FreezeTime: pins the Context date aspect and the legacy execution
 * time globals to a fixed timestamp and restores everything afterwards.
 * Only what FreezeTime itself touched is reverted - the Context singleton is
 * dropped entirely if it did not exist before, otherwise only its date
 * aspect is restored, so a singleton registered by other code during the
 * test survives.
 *
 * Context::getAspect('date') lazily builds a DateTimeAspect from
 * $GLOBALS['EXEC_TIME'] as a side effect, so the previous aspect is
 * snapshotted via reflection on the protected Context::$aspects array
 * instead of calling getAspect().
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

        $globalsSnapshot = [];

        foreach (self::TIME_GLOBALS as $name) {
            $globalsSnapshot[$name] = array_key_exists($name, $GLOBALS) ? $GLOBALS[$name] : null;
        }

        $existedContext = array_key_exists(Context::class, GeneralUtility::getSingletonInstances());
        $context = GeneralUtility::makeInstance(Context::class);

        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        /** @var array<string, AspectInterface> $aspectsSnapshot */
        $aspectsSnapshot = $aspectsProperty->getValue($context);
        $existedDateAspect = array_key_exists('date', $aspectsSnapshot);
        $previousDateAspect = $aspectsSnapshot['date'] ?? null;

        $frozen = new DateTimeImmutable($attribute->dateTime);
        $timestamp = $frozen->getTimestamp();

        $context->setAspect('date', new DateTimeAspect($frozen));

        $GLOBALS['EXEC_TIME'] = $timestamp;
        $GLOBALS['SIM_EXEC_TIME'] = $timestamp;
        $GLOBALS['ACCESS_TIME'] = $timestamp - ($timestamp % 60);
        $GLOBALS['SIM_ACCESS_TIME'] = $timestamp - ($timestamp % 60);

        return static function () use ($globalsSnapshot, $context, $existedContext, $existedDateAspect, $previousDateAspect): void {
            foreach ($globalsSnapshot as $name => $value) {
                if (null === $value) {
                    unset($GLOBALS[$name]);
                } else {
                    $GLOBALS[$name] = $value;
                }
            }

            if (!$existedContext) {
                $singletons = GeneralUtility::getSingletonInstances();
                unset($singletons[Context::class]);
                GeneralUtility::resetSingletonInstances($singletons);

                return;
            }

            if ($existedDateAspect && null !== $previousDateAspect) {
                $context->setAspect('date', $previousDateAspect);
            } else {
                $context->unsetAspect('date');
            }
        };
    }
}

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

namespace KonradMichalik\Ttt\Tests\Handler;

use DateTimeImmutable;
use KonradMichalik\Ttt\Attribute\FreezeTime;
use KonradMichalik\Ttt\Handler\FreezeTimeHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use TYPO3\CMS\Core\Context\{Context, DateTimeAspect};
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * FreezeTimeHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(FreezeTimeHandler::class)]
#[CoversClass(FreezeTime::class)]
final class FreezeTimeHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        // Start each test from a clean baseline so the restorer's unset() branch is exercised.
        unset($GLOBALS['EXEC_TIME'], $GLOBALS['SIM_EXEC_TIME'], $GLOBALS['ACCESS_TIME'], $GLOBALS['SIM_ACCESS_TIME']);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['EXEC_TIME'], $GLOBALS['SIM_EXEC_TIME'], $GLOBALS['ACCESS_TIME'], $GLOBALS['SIM_ACCESS_TIME']);
    }

    #[Test]
    public function pinsDateAspectAndExecutionTimeGlobals(): void
    {
        $restore = (new FreezeTimeHandler())->apply(new FreezeTime('2026-07-14T12:00:30Z'));

        $context = GeneralUtility::makeInstance(Context::class);

        self::assertSame(1784030430, $context->getPropertyFromAspect('date', 'timestamp'));
        self::assertSame(1784030430, $GLOBALS['EXEC_TIME']);
        self::assertSame(1784030400, $GLOBALS['ACCESS_TIME']);

        $restore();

        self::assertArrayNotHasKey('EXEC_TIME', $GLOBALS);
    }

    #[Test]
    public function restoresPreExistingTimeGlobals(): void
    {
        $previous = ['EXEC_TIME' => 111, 'SIM_EXEC_TIME' => 222, 'ACCESS_TIME' => 333, 'SIM_ACCESS_TIME' => 444];

        foreach ($previous as $name => $value) {
            $GLOBALS[$name] = $value;
        }

        $restore = (new FreezeTimeHandler())->apply(new FreezeTime('2026-07-14T12:00:30Z'));
        $restore();

        foreach ($previous as $name => $value) {
            self::assertSame($value, $GLOBALS[$name]);
        }
    }

    #[Test]
    public function contextIsFullyAbsentAfterwardsIfItWasAbsentBefore(): void
    {
        self::assertArrayNotHasKey(Context::class, GeneralUtility::getSingletonInstances());

        $restore = (new FreezeTimeHandler())->apply(new FreezeTime('2026-07-14T12:00:30Z'));
        $restore();

        self::assertArrayNotHasKey(Context::class, GeneralUtility::getSingletonInstances());
    }

    #[Test]
    public function singletonRegisteredDuringTheTestSurvivesRestore(): void
    {
        $restore = (new FreezeTimeHandler())->apply(new FreezeTime('2026-07-14T12:00:30Z'));

        $singleton = new class implements SingletonInterface {};
        GeneralUtility::setSingletonInstance($singleton::class, $singleton);

        $restore();

        self::assertSame($singleton, GeneralUtility::makeInstance($singleton::class));
    }

    #[Test]
    public function restoresAPreviouslySetDateAspectWhenContextAlreadyExisted(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $previousAspect = new DateTimeAspect(new DateTimeImmutable('2020-01-01T00:00:00Z'));
        $context->setAspect('date', $previousAspect);

        $restore = (new FreezeTimeHandler())->apply(new FreezeTime('2026-07-14T12:00:30Z'));
        $restore();

        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        self::assertSame($previousAspect, $aspectsProperty->getValue($context)['date']);
    }

    #[Test]
    public function unsetsDateAspectWhenContextExistedButHadNoDateAspectSet(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        self::assertArrayNotHasKey('date', $aspectsProperty->getValue($context));

        $restore = (new FreezeTimeHandler())->apply(new FreezeTime('2026-07-14T12:00:30Z'));
        $restore();

        self::assertArrayNotHasKey('date', $aspectsProperty->getValue($context));
    }
}

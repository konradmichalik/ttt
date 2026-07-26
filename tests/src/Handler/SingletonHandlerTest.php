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

use KonradMichalik\Ttt\Attribute\WithSingleton;
use KonradMichalik\Ttt\Handler\SingletonHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SingletonHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(SingletonHandler::class)]
#[CoversClass(WithSingleton::class)]
final class SingletonHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function registersInstanceAndRestoresPreviousMap(): void
    {
        $instance = new SingletonFixture();
        $restore = (new SingletonHandler())->apply(new WithSingleton(SingletonFixture::class, $instance));

        self::assertSame($instance, GeneralUtility::makeInstance(SingletonFixture::class));

        $restore();

        self::assertNotSame($instance, GeneralUtility::makeInstance(SingletonFixture::class));
    }

    #[Test]
    public function instantiatesClassStringInstances(): void
    {
        $restore = (new SingletonHandler())->apply(new WithSingleton(SingletonFixture::class, SingletonFixture::class));

        $first = GeneralUtility::makeInstance(SingletonFixture::class);
        $second = GeneralUtility::makeInstance(SingletonFixture::class);

        self::assertSame($first, $second);

        $restore();
    }
}

/**
 * SingletonFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class SingletonFixture implements SingletonInterface {}

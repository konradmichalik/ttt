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

use KonradMichalik\Ttt\Attribute\WithInstance;
use KonradMichalik\Ttt\Handler\InstanceHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * InstanceHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(InstanceHandler::class)]
#[CoversClass(WithInstance::class)]
final class InstanceHandlerTest extends TestCase
{
    private InstanceHandler $subject;

    protected function setUp(): void
    {
        $this->subject = new InstanceHandler();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function supportsOnlyWithInstance(): void
    {
        self::assertTrue($this->subject->supports(new WithInstance(NonSingletonFixture::class, new NonSingletonFixture())));
    }

    #[Test]
    public function instanceIsConsumedByTheFirstMakeInstanceCall(): void
    {
        $fake = new NonSingletonFixture();
        $restore = $this->subject->apply(new WithInstance(NonSingletonFixture::class, $fake));

        self::assertSame($fake, GeneralUtility::makeInstance(NonSingletonFixture::class));

        // Consumed: the next makeInstance() call returns a fresh instance instead.
        self::assertNotSame($fake, GeneralUtility::makeInstance(NonSingletonFixture::class));

        $restore();
    }

    #[Test]
    public function unconsumedInstanceIsPurgedOnRestore(): void
    {
        $fake = new NonSingletonFixture();
        $restore = $this->subject->apply(new WithInstance(NonSingletonFixture::class, $fake));

        $restore();

        // If the fake had leaked, this would return it instead of a fresh instance.
        self::assertNotSame($fake, GeneralUtility::makeInstance(NonSingletonFixture::class));
    }

    #[Test]
    public function restoresAPreviouslyQueuedInstanceForTheSameClass(): void
    {
        $preExisting = new NonSingletonFixture();
        GeneralUtility::addInstance(NonSingletonFixture::class, $preExisting);

        $fake = new NonSingletonFixture();
        $restore = $this->subject->apply(new WithInstance(NonSingletonFixture::class, $fake));

        // FIFO: the pre-existing instance is still consumed first, then the fake.
        self::assertSame($preExisting, GeneralUtility::makeInstance(NonSingletonFixture::class));
        self::assertSame($fake, GeneralUtility::makeInstance(NonSingletonFixture::class));

        $restore();

        // Restored to the pre-attribute queue, regardless of what happened during the test.
        self::assertSame($preExisting, GeneralUtility::makeInstance(NonSingletonFixture::class));
    }

    #[Test]
    public function repeatedAttributesQueueInFifoOrder(): void
    {
        $first = new NonSingletonFixture();
        $second = new NonSingletonFixture();

        $restoreFirst = $this->subject->apply(new WithInstance(NonSingletonFixture::class, $first));
        $restoreSecond = $this->subject->apply(new WithInstance(NonSingletonFixture::class, $second));

        self::assertSame($first, GeneralUtility::makeInstance(NonSingletonFixture::class));
        self::assertSame($second, GeneralUtility::makeInstance(NonSingletonFixture::class));

        $restoreSecond();
        $restoreFirst();
    }
}

/**
 * NonSingletonFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class NonSingletonFixture {}

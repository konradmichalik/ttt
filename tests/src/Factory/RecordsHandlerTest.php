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

namespace KonradMichalik\Ttt\Tests\Factory;

use KonradMichalik\Ttt\Attribute\{WithGlobal, WithRecords};
use KonradMichalik\Ttt\Factory\Records;
use KonradMichalik\Ttt\Handler\RecordsHandler;
use KonradMichalik\Ttt\Tests\Factory\Support\{NewsFactory, PageFactory, SqliteTestCase};
use LogicException;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use stdClass;

/**
 * RecordsHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(RecordsHandler::class)]
#[CoversClass(WithRecords::class)]
#[CoversClass(Records::class)]
final class RecordsHandlerTest extends SqliteTestCase
{
    #[Test]
    public function supportsOnlyTheRecordsAttribute(): void
    {
        $handler = new RecordsHandler();

        self::assertTrue($handler->supports(new WithRecords(NewsFactory::class)));
        self::assertFalse($handler->supports(new WithGlobal('a', 'b')));
    }

    #[Test]
    public function createsRecordsAppliesStatesAndOverridesAndRemovesThemAgain(): void
    {
        $restore = (new RecordsHandler())->apply(new WithRecords(NewsFactory::class, count: 3, states: ['published'], overrides: ['title' => 'Fixed title']));

        $news = Records::of(NewsFactory::class);

        self::assertCount(3, $news);
        self::assertSame(3, $this->countRows('tx_news'));
        self::assertSame('Fixed title', $this->fetchRow('tx_news', $news->first()->uid)['title']);
        self::assertSame('other', $this->fetchRow('tx_news', $news->first()->uid)['type']);

        $restore();

        self::assertSame(0, $this->countRows('tx_news'));
    }

    #[Test]
    public function recordsOfOneFactoryAreMergedAcrossAttributes(): void
    {
        $restoreFirst = (new RecordsHandler())->apply(new WithRecords(NewsFactory::class, count: 2));
        $restoreSecond = (new RecordsHandler())->apply(new WithRecords(NewsFactory::class));

        self::assertCount(3, Records::of(NewsFactory::class));

        $restoreSecond();

        self::assertCount(2, Records::of(NewsFactory::class));

        $restoreFirst();

        self::assertSame(0, $this->countRows('tx_news'));
    }

    #[Test]
    public function recordsOfFailsForAFactoryWithoutRecords(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('#[WithRecords(');

        Records::of(PageFactory::class);
    }

    #[Test]
    public function failsForAClassThatIsNoFactory(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('is not a');

        // @phpstan-ignore argument.type
        (new RecordsHandler())->apply(new WithRecords(stdClass::class));
    }

    #[Test]
    public function failsForAnUnknownState(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('no public state method "nonsense"');

        (new RecordsHandler())->apply(new WithRecords(NewsFactory::class, states: ['nonsense']));
    }

    #[Test]
    public function failsForAStateThatIsOnlyABaseClassMethod(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('no public state method "create"');

        (new RecordsHandler())->apply(new WithRecords(NewsFactory::class, states: ['create']));
    }

    #[Test]
    #[WithRecords(PageFactory::class, count: 2)]
    #[WithRecords(NewsFactory::class, states: ['published'])]
    public function theAttributeWorksOnATestMethodAfterSetUp(): void
    {
        self::assertCount(2, Records::of(PageFactory::class));
        self::assertSame(1, $this->countRows('tx_news'));
    }
}

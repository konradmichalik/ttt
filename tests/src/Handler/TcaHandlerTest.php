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

use KonradMichalik\Ttt\Attribute\{Typo3ConfVarsSentinel, WithTca};
use KonradMichalik\Ttt\Handler\TcaHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

/**
 * TcaHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(TcaHandler::class)]
#[CoversClass(WithTca::class)]
final class TcaHandlerTest extends TestCase
{
    private TcaHandler $subject;

    protected function setUp(): void
    {
        $this->subject = new TcaHandler();
        unset($GLOBALS['TCA']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA']);
    }

    #[Test]
    public function supportsOnlyWithTca(): void
    {
        self::assertTrue($this->subject->supports(new WithTca('tt_content', [])));
    }

    #[Test]
    public function deepMergesConfigurationIntoTheTable(): void
    {
        $GLOBALS['TCA']['tt_content'] = ['ctrl' => ['label' => 'header']];

        $this->subject->apply(new WithTca('tt_content', ['columns' => ['tx_myext_field' => ['config' => ['type' => 'input']]]]));

        self::assertSame('header', $GLOBALS['TCA']['tt_content']['ctrl']['label']);
        self::assertSame('input', $GLOBALS['TCA']['tt_content']['columns']['tx_myext_field']['config']['type']);
    }

    #[Test]
    public function sentinelClearsAKeySetByAnEarlierMerge(): void
    {
        $GLOBALS['TCA']['tt_content'] = ['ctrl' => ['label' => 'header', 'sortby' => 'sorting']];

        $this->subject->apply(new WithTca('tt_content', ['ctrl' => ['sortby' => Typo3ConfVarsSentinel::Unset]]));

        self::assertArrayNotHasKey('sortby', $GLOBALS['TCA']['tt_content']['ctrl']);
        self::assertSame('header', $GLOBALS['TCA']['tt_content']['ctrl']['label']);
    }

    #[Test]
    public function otherTablesAreLeftUntouched(): void
    {
        $GLOBALS['TCA']['pages'] = ['ctrl' => ['label' => 'title']];

        $this->subject->apply(new WithTca('tt_content', ['ctrl' => ['label' => 'header']]));

        self::assertSame('title', $GLOBALS['TCA']['pages']['ctrl']['label']);
    }

    #[Test]
    public function restorerRevertsAPreviouslyConfiguredTableExactly(): void
    {
        $GLOBALS['TCA']['tt_content'] = ['ctrl' => ['label' => 'header']];

        $restore = $this->subject->apply(new WithTca('tt_content', ['ctrl' => ['label' => 'changed']]));
        $restore();

        self::assertSame(['ctrl' => ['label' => 'header']], $GLOBALS['TCA']['tt_content']);
    }

    #[Test]
    public function restorerUnsetsATableThatDidNotExistBefore(): void
    {
        $restore = $this->subject->apply(new WithTca('tx_myext_domain_model_thing', ['ctrl' => []]));
        $restore();

        self::assertArrayNotHasKey('tx_myext_domain_model_thing', $GLOBALS['TCA'] ?? []);
    }

    #[Test]
    public function restorerUnsetsATableWhenTcaGlobalDidNotExistAtAll(): void
    {
        $restore = $this->subject->apply(new WithTca('tt_content', ['ctrl' => []]));
        $restore();

        self::assertArrayNotHasKey('tt_content', $GLOBALS['TCA'] ?? []);
    }
}

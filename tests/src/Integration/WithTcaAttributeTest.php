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

namespace KonradMichalik\Ttt\Tests\Integration;

use KonradMichalik\Ttt\Attribute\WithTca;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * WithTcaAttributeTest.
 *
 * End-to-end proof that the TttExtension (registered in phpunit.xml) applies
 * #[WithTca], merges class-level under method-level, supports repeating the
 * attribute for a second table, and restores both tables afterwards.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[WithTca('tt_content', ['ctrl' => ['label' => 'classLevel']])]
final class WithTcaAttributeTest extends TestCase
{
    #[Test]
    public function classLevelAttributeIsApplied(): void
    {
        self::assertSame('classLevel', $GLOBALS['TCA']['tt_content']['ctrl']['label']);
    }

    #[Test]
    #[WithTca('tt_content', ['ctrl' => ['label' => 'methodLevel']])]
    public function methodLevelAttributeMergesOnTopOfClassLevel(): void
    {
        self::assertSame('methodLevel', $GLOBALS['TCA']['tt_content']['ctrl']['label']);
    }

    #[Test]
    #[WithTca('pages', ['ctrl' => ['label' => 'nav_title']])]
    public function repeatingTheAttributeConfiguresASecondTableIndependently(): void
    {
        self::assertSame('classLevel', $GLOBALS['TCA']['tt_content']['ctrl']['label']);
        self::assertSame('nav_title', $GLOBALS['TCA']['pages']['ctrl']['label']);
    }
}

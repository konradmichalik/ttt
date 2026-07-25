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

namespace KonradMichalik\Ttt\Tests\Traits;

use KonradMichalik\Ttt\Traits\ConfVarsSandbox;
use PHPUnit\Framework\Attributes\{CoversTrait, Test};
use PHPUnit\Framework\TestCase;

/**
 * ConfVarsSandboxTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversTrait(ConfVarsSandbox::class)]
final class ConfVarsSandboxTest extends TestCase
{
    use ConfVarsSandbox;

    protected function setUp(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
    }

    #[Test]
    public function setsAndDeepMergesConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = ['SYS' => ['sitename' => 'Base']];

        $this->setTypo3ConfVars(['EXTCONF' => ['my_ext' => true]]);

        self::assertSame(
            ['SYS' => ['sitename' => 'Base'], 'EXTCONF' => ['my_ext' => true]],
            $GLOBALS['TYPO3_CONF_VARS'],
        );
    }

    #[Test]
    public function restoresPreviousStateEvenAfterRepeatedChanges(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = ['SYS' => ['sitename' => 'Base']];

        $this->setTypo3ConfVars(['SYS' => ['sitename' => 'First']]);
        $this->setTypo3ConfVars(['SYS' => ['sitename' => 'Second']]);

        self::assertSame(['SYS' => ['sitename' => 'Second']], $GLOBALS['TYPO3_CONF_VARS']);

        $this->restoreTypo3ConfVars();

        self::assertSame(['SYS' => ['sitename' => 'Base']], $GLOBALS['TYPO3_CONF_VARS']);
    }
}

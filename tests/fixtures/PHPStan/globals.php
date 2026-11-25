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

namespace KonradMichalik\Ttt\Tests\Fixtures\PHPStan;

use KonradMichalik\Ttt\Attribute\{WithTca, WithTypo3ConfVars};
use PHPUnit\Framework\TestCase;


/**
 * PlainTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

final class PlainTest extends TestCase
{
    public function nested(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['my_ext'] = [];
        $GLOBALS['TCA']['tt_content']['ctrl']['title'] = 'x';
    }

    public function replacingTheWholeGlobalIsNotReported(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = ['A' => 1];
        $GLOBALS['TYPO3_CONF_VARS'] = 'not-an-array';
    }

    public function otherKeysAreIgnored(): void
    {
        $GLOBALS['BE_USER'] = null;
        $GLOBALS['LANG'] = null;
        $other = [];
        $other['TCA']['x'] = 1;
    }
}


/**
 * NotATest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

final class NotATest
{
    public function run(): void
    {
        $GLOBALS['TCA']['x']['y'] = 1;
    }
}


/**
 * ClassSandboxedTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

#[WithTypo3ConfVars(['A' => 1])]
final class ClassSandboxedTest extends TestCase
{
    public function confVarsCovered(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['A']['b'] = 1;
    }

    public function tcaNotCovered(): void
    {
        $GLOBALS['TCA']['x']['y'] = 1;
    }
}


/**
 * MethodSandboxedTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

final class MethodSandboxedTest extends TestCase
{
    #[WithTca('x', ['ctrl' => []])]
    public function tcaCovered(): void
    {
        $GLOBALS['TCA']['x']['y'] = 1;
    }

    public function tcaOtherMethod(): void
    {
        $GLOBALS['TCA']['x']['y'] = 2;
    }
}


/**
 * AttributedBaseTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

#[WithTca('tt_content', ['ctrl' => []])]
abstract class AttributedBaseTest extends TestCase {}


/**
 * InheritingTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

abstract class InheritingTest extends AttributedBaseTest
{
    public function inheritedFromParent(): void
    {
        $GLOBALS['TCA']['x']['y'] = 1;
    }
}


/**
 * GrandchildTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

final class GrandchildTest extends InheritingTest
{
    public function inheritedFromGrandparent(): void
    {
        $GLOBALS['TCA']['x']['y'] = 2;
    }
}


/**
 * UnrelatedSiblingTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

final class UnrelatedSiblingTest extends TestCase
{
    public function notCovered(): void
    {
        $GLOBALS['TCA']['x']['y'] = 3;
    }
}

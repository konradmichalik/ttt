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

namespace KonradMichalik\Ttt\Tests\PHPStan;

use KonradMichalik\Ttt\PHPStan\{ManualGlobalsRule, TestContext};
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Test};

/**
 * ManualGlobalsRuleTest.
 *
 * @extends RuleTestCase<ManualGlobalsRule>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ManualGlobalsRule::class)]
final class ManualGlobalsRuleTest extends RuleTestCase
{
    #[Test]
    public function reportsGlobalsSetByHandUnlessAnAttributeCoversThem(): void
    {
        $this->analyse([__DIR__.'/../../fixtures/PHPStan/globals.php'], [
            ['$GLOBALS[\'TYPO3_CONF_VARS\'] is set by hand in a test, use #[WithTypo3ConfVars] instead.', 31],
            ['$GLOBALS[\'TCA\'] is set by hand in a test, use #[WithTca] instead.', 32],
            ['$GLOBALS[\'TCA\'] is set by hand in a test, use #[WithTca] instead.', 84],
            ['$GLOBALS[\'TCA\'] is set by hand in a test, use #[WithTca] instead.', 106],
            ['$GLOBALS[\'TCA\'] is set by hand in a test, use #[WithTca] instead.', 165],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ManualGlobalsRule(new TestContext());
    }
}

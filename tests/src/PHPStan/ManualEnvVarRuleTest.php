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

use KonradMichalik\Ttt\PHPStan\{ManualEnvVarRule, TestContext};
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Test};

/**
 * ManualEnvVarRuleTest.
 *
 * @extends RuleTestCase<ManualEnvVarRule>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ManualEnvVarRule::class)]
final class ManualEnvVarRuleTest extends RuleTestCase
{
    #[Test]
    public function reportsPutenvInTestCases(): void
    {
        $message = 'Environment variable set or cleared with putenv() in a test, use #[WithEnvVar] instead.';

        $this->analyse([__DIR__.'/../../fixtures/PHPStan/env.php'], [
            [$message, 30],
            [$message, 31],
            [$message, 32],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ManualEnvVarRule(new TestContext());
    }
}

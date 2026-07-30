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

use KonradMichalik\Ttt\Handler\FunctionalTestCaseGuard;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

/**
 * FunctionalTestCaseGuardTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(FunctionalTestCaseGuard::class)]
final class FunctionalTestCaseGuardTest extends TestCase
{
    #[Test]
    public function doesNothingWhenNotRunningOnAFunctionalTestCase(): void
    {
        FunctionalTestCaseGuard::assertNotFunctionalTestCase('SomeAttribute', 'reason', 'alternative');

        self::expectNotToPerformAssertions();
    }
}

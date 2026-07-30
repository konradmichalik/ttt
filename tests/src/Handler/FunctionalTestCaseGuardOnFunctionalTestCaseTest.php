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
use RuntimeException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * FunctionalTestCaseGuardOnFunctionalTestCaseTest.
 *
 * Extends the typo3/testing-framework stub (see tests/stubs) instead of
 * PHPUnit's plain TestCase, so FunctionalTestCaseGuard sees a real
 * FunctionalTestCase subclass in the call stack.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(FunctionalTestCaseGuard::class)]
final class FunctionalTestCaseGuardOnFunctionalTestCaseTest extends FunctionalTestCase
{
    #[Test]
    public function throwsForAnAttributeStructurallyIncompatibleWithFunctionalTestCase(): void
    {
        try {
            FunctionalTestCaseGuard::assertNotFunctionalTestCase('SomeAttribute', 'the reason', 'the alternative');
            self::fail('Expected a RuntimeException.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('SomeAttribute cannot be used on FunctionalTestCase', $exception->getMessage());
            self::assertStringContainsString('the reason', $exception->getMessage());
            self::assertStringContainsString('the alternative', $exception->getMessage());
        }
    }
}

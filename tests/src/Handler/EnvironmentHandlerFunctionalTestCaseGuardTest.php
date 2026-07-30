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

use KonradMichalik\Ttt\Attribute\WithEnvironment;
use KonradMichalik\Ttt\Handler\EnvironmentHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use RuntimeException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * EnvironmentHandlerFunctionalTestCaseGuardTest.
 *
 * Extends the typo3/testing-framework stub (see tests/stubs), not PHPUnit's
 * plain TestCase, to prove #[WithEnvironment] fails loudly on FunctionalTestCase.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(EnvironmentHandler::class)]
final class EnvironmentHandlerFunctionalTestCaseGuardTest extends FunctionalTestCase
{
    #[Test]
    public function applyFailsLoudlyOnFunctionalTestCase(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('WithEnvironment cannot be used on FunctionalTestCase');

        (new EnvironmentHandler())->apply(new WithEnvironment());
    }
}

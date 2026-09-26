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

namespace KonradMichalik\Ttt\Tests\Fixtures\ProcessIsolation;

use KonradMichalik\Ttt\Attribute\WithEnvVar;
use PHPUnit\Framework\Attributes\{RunInSeparateProcess, Test};
use PHPUnit\Framework\TestCase;

/**
 * ProcessIsolationFixtureTest.
 *
 * Executed by ProcessIsolationTest in a separate PHPUnit run, never by the
 * main suite.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ProcessIsolationFixtureTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[WithEnvVar('TTT_ISOLATION_VAR', 'on')]
    public function attributedTestInSeparateProcess(): void
    {
        self::expectNotToPerformAssertions();
    }

    #[Test]
    #[RunInSeparateProcess]
    public function plainTestInSeparateProcess(): void
    {
        self::expectNotToPerformAssertions();
    }
}

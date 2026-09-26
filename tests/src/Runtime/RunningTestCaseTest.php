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

namespace KonradMichalik\Ttt\Tests\Runtime;

use KonradMichalik\Ttt\Runtime\RunningTestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

/**
 * RunningTestCaseTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(RunningTestCase::class)]
final class RunningTestCaseTest extends TestCase
{
    #[Test]
    public function findsTheTestCaseRunningFurtherUpTheCallStack(): void
    {
        self::assertSame($this, RunningTestCase::find());
    }
}

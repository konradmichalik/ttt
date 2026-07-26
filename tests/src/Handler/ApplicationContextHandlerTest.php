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

use KonradMichalik\Ttt\Attribute\InApplicationContext;
use KonradMichalik\Ttt\Handler\ApplicationContextHandler;
use PHPUnit\Framework\Attributes\{CoversClass, PreserveGlobalState, RunInSeparateProcess, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ApplicationContextHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ApplicationContextHandler::class)]
#[CoversClass(InApplicationContext::class)]
final class ApplicationContextHandlerTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function failsWhenTheEnvironmentIsNotInitialized(): void
    {
        // A fresh process guarantees TYPO3's Environment has never been initialized.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1752561601);

        (new ApplicationContextHandler())->apply(new InApplicationContext('Development'));
    }
}

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

use KonradMichalik\Ttt\Traits\EnvVarSandbox;
use PHPUnit\Framework\Attributes\{CoversTrait, Test};
use PHPUnit\Framework\TestCase;

/**
 * EnvVarSandboxTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversTrait(EnvVarSandbox::class)]
final class EnvVarSandboxTest extends TestCase
{
    use EnvVarSandbox;

    private const NAME = 'TTT_ENV_VAR_SANDBOX_TEST';

    protected function tearDown(): void
    {
        $this->restoreEnvVars();
        putenv(self::NAME);
        unset($_ENV[self::NAME], $_SERVER[self::NAME]);
    }

    #[Test]
    public function setsEnvVarAcrossAllThreeChannels(): void
    {
        $this->setEnvVar(self::NAME, 'first');

        self::assertSame('first', getenv(self::NAME));
        self::assertSame('first', $_ENV[self::NAME]);
        self::assertSame('first', $_SERVER[self::NAME]);
    }

    #[Test]
    public function restoresPreviousStateEvenAfterRepeatedChanges(): void
    {
        $this->setEnvVar(self::NAME, 'first');
        $this->setEnvVar(self::NAME, 'second');

        self::assertSame('second', getenv(self::NAME));

        $this->restoreEnvVars();

        self::assertFalse(getenv(self::NAME));
        self::assertArrayNotHasKey(self::NAME, $_ENV);
        self::assertArrayNotHasKey(self::NAME, $_SERVER);
    }
}

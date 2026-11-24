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

use KonradMichalik\Ttt\Traits\{ConfVarsSandbox, EnvVarSandbox};
use PHPUnit\Framework\Attributes\{CoversTrait, Depends, Test};
use PHPUnit\Framework\TestCase;

/**
 * SandboxTraitsAfterHookTest.
 *
 * Deliberately has no tearDown(): the traits must restore their state through
 * their own #[After] hook.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversTrait(ConfVarsSandbox::class)]
#[CoversTrait(EnvVarSandbox::class)]
final class SandboxTraitsAfterHookTest extends TestCase
{
    use ConfVarsSandbox;
    use EnvVarSandbox;

    private const NAME = 'TTT_AFTER_HOOK_TEST';

    #[Test]
    public function changesState(): void
    {
        $this->setTypo3ConfVars(['SYS' => ['sitename' => 'after hook']]);
        $this->setEnvVar(self::NAME, 'set');

        self::assertSame('after hook', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        self::assertSame('set', getenv(self::NAME));
    }

    #[Test]
    #[Depends('changesState')]
    public function stateIsRestoredWithoutATearDown(): void
    {
        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
        self::assertFalse(getenv(self::NAME));
        self::assertArrayNotHasKey(self::NAME, $_ENV);
    }

    #[Test]
    public function acceptsNullToUnsetAnEnvVar(): void
    {
        putenv(self::NAME.'=present');

        $this->setEnvVar(self::NAME, null);

        self::assertFalse(getenv(self::NAME));

        $this->restoreEnvVars();

        self::assertSame('present', getenv(self::NAME));
        putenv(self::NAME);
    }
}

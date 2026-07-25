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

use InvalidArgumentException;
use KonradMichalik\Ttt\Attribute\WithEnvVar;
use KonradMichalik\Ttt\Handler\EnvVarHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

/**
 * EnvVarHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(EnvVarHandler::class)]
final class EnvVarHandlerTest extends TestCase
{
    private const VAR = 'TTT_TEST_VAR';

    private EnvVarHandler $subject;

    protected function setUp(): void
    {
        $this->subject = new EnvVarHandler();
    }

    protected function tearDown(): void
    {
        putenv(self::VAR);
        unset($_ENV[self::VAR], $_SERVER[self::VAR]);
    }

    #[Test]
    public function rejectsInvalidVariableNames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1752561605);

        $this->subject->apply(new WithEnvVar('EVIL=INJECTED', 'x'));
    }

    #[Test]
    public function appliesValueToAllThreeChannels(): void
    {
        $this->subject->apply(new WithEnvVar(self::VAR, 'on'));

        self::assertSame('on', getenv(self::VAR));
        self::assertSame('on', $_ENV[self::VAR]);
        self::assertSame('on', $_SERVER[self::VAR]);
    }

    #[Test]
    public function restorerRemovesPreviouslyUnsetVariable(): void
    {
        $restore = $this->subject->apply(new WithEnvVar(self::VAR, 'on'));
        $restore();

        self::assertFalse(getenv(self::VAR));
        self::assertArrayNotHasKey(self::VAR, $_ENV);
        self::assertArrayNotHasKey(self::VAR, $_SERVER);
    }

    #[Test]
    public function restorerRevertsPreviouslySetVariable(): void
    {
        putenv(self::VAR.'=before');
        $_ENV[self::VAR] = 'before';
        $_SERVER[self::VAR] = 'before';

        $restore = $this->subject->apply(new WithEnvVar(self::VAR, 'after'));
        $restore();

        self::assertSame('before', getenv(self::VAR));
        self::assertSame('before', $_ENV[self::VAR]);
        self::assertSame('before', $_SERVER[self::VAR]);
    }
}

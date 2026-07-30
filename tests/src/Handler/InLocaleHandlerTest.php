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

use KonradMichalik\Ttt\Attribute\InLocale;
use KonradMichalik\Ttt\Handler\InLocaleHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function setlocale;

/**
 * InLocaleHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(InLocaleHandler::class)]
#[CoversClass(InLocale::class)]
final class InLocaleHandlerTest extends TestCase
{
    private string|false $originalLocale;

    private InLocaleHandler $subject;

    protected function setUp(): void
    {
        $this->originalLocale = setlocale(\LC_CTYPE, '0');
        $this->subject = new InLocaleHandler();
    }

    protected function tearDown(): void
    {
        if (false !== $this->originalLocale) {
            setlocale(\LC_CTYPE, $this->originalLocale);
        }
    }

    #[Test]
    public function setsAndRestoresTheLocaleForTheGivenCategory(): void
    {
        setlocale(\LC_CTYPE, 'C');
        $baseline = setlocale(\LC_CTYPE, '0');
        self::assertIsString($baseline);

        // 'POSIX' is a guaranteed-available alias for 'C' on POSIX systems, but
        // platforms differ on whether setlocale() then reports back 'POSIX' or
        // normalizes it to 'C' - learn the expected value instead of assuming it.
        $expected = setlocale(\LC_CTYPE, 'POSIX');
        setlocale(\LC_CTYPE, $baseline);

        $restore = $this->subject->apply(new InLocale(\LC_CTYPE, 'POSIX'));

        self::assertSame($expected, setlocale(\LC_CTYPE, '0'));

        $restore();

        self::assertSame($baseline, setlocale(\LC_CTYPE, '0'));
    }

    #[Test]
    public function throwsWhenTheRequestedLocaleIsUnavailable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1753900203);
        $this->expectExceptionMessage('not-a-real-locale-xyz');

        $this->subject->apply(new InLocale(\LC_CTYPE, 'not-a-real-locale-xyz'));
    }

    #[Test]
    public function unavailableLocaleLeavesThePreviousLocaleUnchanged(): void
    {
        setlocale(\LC_CTYPE, 'C');

        try {
            $this->subject->apply(new InLocale(\LC_CTYPE, 'not-a-real-locale-xyz'));
        } catch (RuntimeException) {
            // Expected - assertion is about the locale, not the exception.
        }

        self::assertSame('C', setlocale(\LC_CTYPE, '0'));
    }
}

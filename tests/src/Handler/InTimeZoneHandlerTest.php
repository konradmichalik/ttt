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

use KonradMichalik\Ttt\Attribute\InTimeZone;
use KonradMichalik\Ttt\Handler\InTimeZoneHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function date_default_timezone_get;
use function date_default_timezone_set;

/**
 * InTimeZoneHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(InTimeZoneHandler::class)]
#[CoversClass(InTimeZone::class)]
final class InTimeZoneHandlerTest extends TestCase
{
    private string $originalTimeZone;

    private InTimeZoneHandler $subject;

    protected function setUp(): void
    {
        $this->originalTimeZone = date_default_timezone_get();
        $this->subject = new InTimeZoneHandler();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimeZone);
    }

    #[Test]
    public function setsAndRestoresTheDefaultTimeZone(): void
    {
        date_default_timezone_set('America/New_York');

        $restore = $this->subject->apply(new InTimeZone('Europe/Berlin'));

        self::assertSame('Europe/Berlin', date_default_timezone_get());

        $restore();

        self::assertSame('America/New_York', date_default_timezone_get());
    }

    #[Test]
    public function throwsForAnInvalidTimeZoneIdentifier(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1753900201);
        $this->expectExceptionMessage('Not/AZoneAtAll');

        $this->subject->apply(new InTimeZone('Not/AZoneAtAll'));
    }

    #[Test]
    public function invalidTimeZoneLeavesThePreviousTimeZoneUnchanged(): void
    {
        date_default_timezone_set('UTC');

        try {
            $this->subject->apply(new InTimeZone('Not/AZoneAtAll'));
        } catch (RuntimeException) {
            // Expected - assertion is about the timezone, not the exception.
        }

        self::assertSame('UTC', date_default_timezone_get());
    }
}

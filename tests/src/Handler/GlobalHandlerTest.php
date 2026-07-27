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

use KonradMichalik\Ttt\Attribute\WithGlobal;
use KonradMichalik\Ttt\Handler\GlobalHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

/**
 * GlobalHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(GlobalHandler::class)]
#[CoversClass(WithGlobal::class)]
final class GlobalHandlerTest extends TestCase
{
    private const KEY = 'TTT_GLOBAL_HANDLER_TEST';

    protected function tearDown(): void
    {
        unset($GLOBALS[self::KEY]);
    }

    #[Test]
    public function setsGlobalAndRestoresUnsetKey(): void
    {
        self::assertArrayNotHasKey(self::KEY, $GLOBALS);

        $restore = (new GlobalHandler())->apply(new WithGlobal(self::KEY, 'value'));

        self::assertSame('value', $GLOBALS[self::KEY]);

        $restore();

        self::assertArrayNotHasKey(self::KEY, $GLOBALS);
    }

    #[Test]
    public function restoresPreviousValue(): void
    {
        $GLOBALS[self::KEY] = 'original';

        $restore = (new GlobalHandler())->apply(new WithGlobal(self::KEY, 'overridden'));
        self::assertSame('overridden', $GLOBALS[self::KEY]);

        $restore();

        self::assertSame('original', $GLOBALS[self::KEY]);
    }
}

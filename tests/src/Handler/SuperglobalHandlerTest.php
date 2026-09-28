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
use KonradMichalik\Ttt\Attribute\WithSuperglobal;
use KonradMichalik\Ttt\Handler\SuperglobalHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

/**
 * SuperglobalHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(SuperglobalHandler::class)]
#[CoversClass(WithSuperglobal::class)]
final class SuperglobalHandlerTest extends TestCase
{
    private const KEY = 'TTT_SUPERGLOBAL_HANDLER_TEST';

    protected function tearDown(): void
    {
        unset($_SERVER[self::KEY], $_GET[self::KEY]);
    }

    #[Test]
    public function setsSuperglobalAndRestoresUnsetKey(): void
    {
        self::assertArrayNotHasKey(self::KEY, $_SERVER);

        $restore = (new SuperglobalHandler())->apply(new WithSuperglobal('_SERVER', self::KEY, 'value'));

        self::assertSame('value', $_SERVER[self::KEY]);

        $restore();

        self::assertArrayNotHasKey(self::KEY, $_SERVER);
    }

    #[Test]
    public function restoresPreviousValue(): void
    {
        $_SERVER[self::KEY] = 'original';

        $restore = (new SuperglobalHandler())->apply(new WithSuperglobal('_SERVER', self::KEY, 'overridden'));
        self::assertSame('overridden', $_SERVER[self::KEY]);

        $restore();

        self::assertSame('original', $_SERVER[self::KEY]);
    }

    #[Test]
    public function unsetsExistingKeyWhenValueIsNull(): void
    {
        $_GET[self::KEY] = 'original';

        $restore = (new SuperglobalHandler())->apply(new WithSuperglobal('_GET', self::KEY, null));
        self::assertArrayNotHasKey(self::KEY, $_GET);

        $restore();

        self::assertSame('original', $_GET[self::KEY]);
    }

    #[Test]
    public function restoresPreviouslyUnsetKeyAfterSettingIt(): void
    {
        self::assertArrayNotHasKey(self::KEY, $_GET);

        $restore = (new SuperglobalHandler())->apply(new WithSuperglobal('_GET', self::KEY, 'value'));
        self::assertSame('value', $_GET[self::KEY]);

        $restore();

        self::assertArrayNotHasKey(self::KEY, $_GET);
    }

    #[Test]
    public function rejectsUnsupportedSuperglobal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SuperglobalHandler())->apply(new WithSuperglobal('_SESSION', self::KEY, 'value'));
    }
}

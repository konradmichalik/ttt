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

use KonradMichalik\Ttt\Attribute\WithStaticProperty;
use KonradMichalik\Ttt\Handler\StaticPropertyHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

/**
 * StaticPropertyHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(StaticPropertyHandler::class)]
#[CoversClass(WithStaticProperty::class)]
final class StaticPropertyHandlerTest extends TestCase
{
    private StaticPropertyHandler $subject;

    protected function setUp(): void
    {
        $this->subject = new StaticPropertyHandler();
        StaticPropertyFixture::$publicValue = 'public-default';
        StaticPropertyFixture::resetProtected('protected-default');
        StaticPropertyFixture::resetPrivate('private-default');
    }

    #[Test]
    public function supportsOnlyWithStaticProperty(): void
    {
        self::assertTrue($this->subject->supports(new WithStaticProperty(StaticPropertyFixture::class, 'publicValue', 'x')));
    }

    #[Test]
    public function setsAndRestoresAPublicStaticProperty(): void
    {
        $restore = $this->subject->apply(new WithStaticProperty(StaticPropertyFixture::class, 'publicValue', 'sandboxed'));

        self::assertSame('sandboxed', StaticPropertyFixture::$publicValue);

        $restore();

        self::assertSame('public-default', StaticPropertyFixture::$publicValue);
    }

    #[Test]
    public function setsAndRestoresAProtectedStaticProperty(): void
    {
        $restore = $this->subject->apply(new WithStaticProperty(StaticPropertyFixture::class, 'protectedValue', 'sandboxed'));

        self::assertSame('sandboxed', StaticPropertyFixture::readProtected());

        $restore();

        self::assertSame('protected-default', StaticPropertyFixture::readProtected());
    }

    #[Test]
    public function setsAndRestoresAPrivateStaticProperty(): void
    {
        $restore = $this->subject->apply(new WithStaticProperty(StaticPropertyFixture::class, 'privateValue', 'sandboxed'));

        self::assertSame('sandboxed', StaticPropertyFixture::readPrivate());

        $restore();

        self::assertSame('private-default', StaticPropertyFixture::readPrivate());
    }

    #[Test]
    public function throwsForAnUninitializedProperty(): void
    {
        $property = new ReflectionProperty(StaticPropertyFixture::class, 'uninitializedValue');
        self::assertFalse($property->isInitialized());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1753900302);
        $this->expectExceptionMessage('uninitializedValue');

        $this->subject->apply(new WithStaticProperty(StaticPropertyFixture::class, 'uninitializedValue', 'sandboxed'));
    }
}

/**
 * StaticPropertyFixtureBase.
 *
 * Not final and extended below: keeps $protectedValue genuinely protected
 * (a final class with no inheritance would have PHP CS Fixer's
 * protected_to_private rule downgrade it to private).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class StaticPropertyFixtureBase
{
    protected static string $protectedValue = 'protected-default';

    public static function readProtected(): string
    {
        return static::$protectedValue;
    }

    public static function resetProtected(string $value): void
    {
        static::$protectedValue = $value;
    }
}

/**
 * StaticPropertyFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class StaticPropertyFixture extends StaticPropertyFixtureBase
{
    public static string $publicValue = 'public-default';

    public static string $uninitializedValue;

    private static string $privateValue = 'private-default';

    public static function readPrivate(): string
    {
        return self::$privateValue;
    }

    public static function resetPrivate(string $value): void
    {
        self::$privateValue = $value;
    }
}

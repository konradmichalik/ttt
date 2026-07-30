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

namespace KonradMichalik\Ttt\Tests;

use Closure;
use KonradMichalik\Ttt\Attribute\TttAttribute;
use KonradMichalik\Ttt\Handler\AttributeHandler;
use KonradMichalik\Ttt\TttExtension;
use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\{Facade, ParameterCollection};
use PHPUnit\TextUI\Configuration\Configuration;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use stdClass;

/**
 * TttExtensionTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(TttExtension::class)]
final class TttExtensionTest extends TestCase
{
    #[Test]
    public function bootstrapBuildsTheRegistryAndHandsSubscribersToTheEventFacade(): void
    {
        $facade = new Facade();

        // Configuration is final readonly and unused by bootstrap(), so a bare instance suffices.
        $configuration = (new ReflectionClass(Configuration::class))->newInstanceWithoutConstructor();

        // Both subscribers are constructed (with the fully populated registry) before being
        // handed to PHPUnit's event facade - which is already sealed inside a running suite,
        // so the registration attempt proves the wiring executed end to end.
        $this->expectException(EventFacadeIsSealedException::class);

        (new TttExtension())->bootstrap($configuration, $facade, ParameterCollection::fromArray([]));
    }

    #[Test]
    public function customHandlersReturnsNothingWhenTheHandlersParameterIsAbsent(): void
    {
        self::assertSame([], self::customHandlers(ParameterCollection::fromArray([])));
    }

    #[Test]
    public function customHandlersResolvesASingleFullyQualifiedClassName(): void
    {
        $handlers = self::customHandlers(ParameterCollection::fromArray(['handlers' => CustomHandlerFixture::class]));

        self::assertCount(1, $handlers);
        self::assertInstanceOf(CustomHandlerFixture::class, $handlers[0]);
    }

    #[Test]
    public function customHandlersResolvesCommaSeparatedClassNamesAndTrimsWhitespace(): void
    {
        $handlers = self::customHandlers(ParameterCollection::fromArray([
            'handlers' => CustomHandlerFixture::class.' , '.AnotherCustomHandlerFixture::class,
        ]));

        self::assertCount(2, $handlers);
        self::assertInstanceOf(CustomHandlerFixture::class, $handlers[0]);
        self::assertInstanceOf(AnotherCustomHandlerFixture::class, $handlers[1]);
    }

    #[Test]
    public function customHandlersThrowsForAnUnknownClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('handlers');
        $this->expectExceptionMessage('Not\A\Real\Class');

        self::customHandlers(ParameterCollection::fromArray(['handlers' => 'Not\A\Real\Class']));
    }

    #[Test]
    public function customHandlersThrowsWhenTheClassDoesNotImplementAttributeHandler(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('handlers');
        $this->expectExceptionMessage(AttributeHandler::class);

        self::customHandlers(ParameterCollection::fromArray(['handlers' => stdClass::class]));
    }

    /**
     * @return list<AttributeHandler>
     */
    private static function customHandlers(ParameterCollection $parameters): array
    {
        $method = new ReflectionMethod(TttExtension::class, 'customHandlers');

        /* @var list<AttributeHandler> */
        return $method->invoke(null, $parameters);
    }
}

/**
 * CustomHandlerFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class CustomHandlerFixture implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return false;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        return static function (): void {};
    }
}

/**
 * AnotherCustomHandlerFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class AnotherCustomHandlerFixture implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return false;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        return static function (): void {};
    }
}

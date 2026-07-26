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

use KonradMichalik\Ttt\TttExtension;
use PHPUnit\Event\EventFacadeIsSealedException;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\{Facade, ParameterCollection};
use PHPUnit\TextUI\Configuration\Configuration;
use ReflectionClass;

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
}

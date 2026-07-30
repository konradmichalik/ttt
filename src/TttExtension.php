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

namespace KonradMichalik\Ttt;

use KonradMichalik\Ttt\Handler\{ApplicationContextHandler, BackendUserHandler, ConfVarsHandler, EnvVarHandler, EnvironmentHandler, FreezeTimeHandler, GlobalHandler, InLocaleHandler, InTimeZoneHandler, SingletonHandler};
use KonradMichalik\Ttt\Registry\SandboxRegistry;
use KonradMichalik\Ttt\Subscriber\{ApplySandboxSubscriber, RestoreSandboxSubscriber};
use PHPUnit\Runner\Extension\{Extension, Facade, ParameterCollection};
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * TttExtension.
 *
 * PHPUnit extension entry point. Register it once in your phpunit.xml:
 *
 * <code>
 * <extensions>
 *     <bootstrap class="KonradMichalik\Ttt\TttExtension"/>
 * </extensions>
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class TttExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $registry = new SandboxRegistry([
            new ConfVarsHandler(),
            new EnvVarHandler(),
            new EnvironmentHandler(),
            new ApplicationContextHandler(),
            new SingletonHandler(),
            new BackendUserHandler(),
            new FreezeTimeHandler(),
            new GlobalHandler(),
            new InTimeZoneHandler(),
            new InLocaleHandler(),
        ]);

        $facade->registerSubscribers(
            new ApplySandboxSubscriber($registry),
            new RestoreSandboxSubscriber($registry),
        );
    }
}

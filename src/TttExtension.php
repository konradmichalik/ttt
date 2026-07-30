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

use KonradMichalik\Ttt\Handler\{ApplicationContextHandler, AttributeHandler, BackendUserHandler, ConfVarsHandler, EnvVarHandler, EnvironmentHandler, FreezeTimeHandler, GlobalHandler, InstanceHandler, SingletonHandler};
use KonradMichalik\Ttt\Registry\SandboxRegistry;
use KonradMichalik\Ttt\Subscriber\{ApplySandboxSubscriber, RestoreSandboxSubscriber};
use PHPUnit\Runner\Extension\{Extension, Facade, ParameterCollection};
use PHPUnit\TextUI\Configuration\Configuration;
use RuntimeException;

use function array_filter;
use function array_map;
use function array_values;
use function class_exists;
use function explode;
use function is_a;
use function sprintf;
use function trim;

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
 * Custom handlers (in addition to the built-in ones) can be registered via a
 * comma-separated "handlers" parameter naming AttributeHandler implementations:
 *
 * <code>
 * <extensions>
 *     <bootstrap class="KonradMichalik\Ttt\TttExtension">
 *         <parameter name="handlers" value="Vendor\Ext\Tests\Sandbox\MyHandler,Vendor\Ext\Tests\Sandbox\OtherHandler" />
 *     </bootstrap>
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
            new InstanceHandler(),
            ...self::customHandlers($parameters),
        ]);

        $facade->registerSubscribers(
            new ApplySandboxSubscriber($registry),
            new RestoreSandboxSubscriber($registry),
        );
    }

    /**
     * @return list<AttributeHandler>
     */
    private static function customHandlers(ParameterCollection $parameters): array
    {
        if (!$parameters->has('handlers')) {
            return [];
        }

        $classNames = array_values(array_filter(array_map(trim(...), explode(',', $parameters->get('handlers')))));

        return array_map(static function (string $className): AttributeHandler {
            if (!class_exists($className)) {
                throw new RuntimeException(sprintf('TttExtension "handlers" parameter references unknown class "%s".', $className), 1753900101);
            }

            if (!is_a($className, AttributeHandler::class, true)) {
                throw new RuntimeException(sprintf('TttExtension "handlers" parameter class "%s" must implement %s.', $className, AttributeHandler::class), 1753900102);
            }

            return new $className();
        }, $classNames);
    }
}

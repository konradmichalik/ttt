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

namespace KonradMichalik\Ttt\Handler;

use Closure;
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithInstance};
use ReflectionProperty;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function assert;

/**
 * InstanceHandler.
 *
 * Applies WithInstance: queues the instance via GeneralUtility::addInstance()
 * and restores the previous queue for that class name afterwards - whether
 * the attribute's instance was consumed by a makeInstance() call during the
 * test or not. See WithInstance's docblock for the restore-only scope.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class InstanceHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithInstance;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithInstance);

        $className = $attribute->className;
        $before = GeneralUtility::getInstances()[$className] ?? [];

        GeneralUtility::addInstance($className, $attribute->instance);

        return static function () use ($className, $before): void {
            $instancesProperty = new ReflectionProperty(GeneralUtility::class, 'nonSingletonInstances');
            /** @var array<class-string, list<object>> $current */
            $current = $instancesProperty->getValue();

            if ([] === $before) {
                unset($current[$className]);
            } else {
                $current[$className] = $before;
            }

            $instancesProperty->setValue(null, $current);
        };
    }
}

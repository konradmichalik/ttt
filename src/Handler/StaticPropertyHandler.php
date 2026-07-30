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
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithStaticProperty};
use ReflectionProperty;
use RuntimeException;

use function assert;
use function sprintf;

/**
 * StaticPropertyHandler.
 *
 * Applies WithStaticProperty: overwrites a static property via reflection
 * and restores the previous value afterwards. See WithStaticProperty's
 * docblock for the two cases that fail loudly instead of being silently
 * applied (readonly, not yet initialized).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class StaticPropertyHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithStaticProperty;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithStaticProperty);

        $property = new ReflectionProperty($attribute->className, $attribute->propertyName);

        if ($property->isReadOnly()) {
            // Defensive: PHP does not allow declaring a static property as
            // readonly in the first place ("Static property ... cannot be
            // readonly"), so this branch is currently unreachable - kept in
            // case a future PHP version lifts that restriction.
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('WithStaticProperty: "%s::$%s" is readonly and cannot be sandboxed.', $attribute->className, $attribute->propertyName), 1753900301);
            // @codeCoverageIgnoreEnd
        }

        if (!$property->isInitialized()) {
            throw new RuntimeException(sprintf('WithStaticProperty: "%s::$%s" is not initialized and cannot be sandboxed - PHP has no way to revert a typed property to the uninitialized state, and restoring it to null would silently change its behavior.', $attribute->className, $attribute->propertyName), 1753900302);
        }

        $previousValue = $property->getValue();

        $property->setValue(null, $attribute->value);

        return static function () use ($property, $previousValue): void {
            $property->setValue(null, $previousValue);
        };
    }
}

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

namespace KonradMichalik\Ttt\Reflection;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

use function implode;
use function sprintf;

/**
 * ObjectBuilder.
 *
 * Fluent builder constructing an instance without calling its constructor
 * and injecting property values via reflection - replacing hand-written
 * "(new ReflectionClass($class))->newInstanceWithoutConstructor()" plus a
 * per-property ReflectionProperty::setValue() loop in tests. Works for
 * private, protected and (uninitialized) readonly typed properties alike.
 *
 * <code>
 * $instance = ObjectBuilder::withoutConstructor(StorageController::class)
 *     ->withProperty('connectionPool', $connectionPoolStub)
 *     ->build();
 * </code>
 *
 * @template T of object
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ObjectBuilder
{
    /** @var array<string, mixed> */
    private array $properties = [];

    /**
     * @param class-string<T> $className
     */
    private function __construct(
        private readonly string $className,
    ) {}

    /**
     * @template U of object
     *
     * @param class-string<U> $className
     *
     * @return self<U>
     */
    public static function withoutConstructor(string $className): self
    {
        return new self($className);
    }

    /**
     * @return self<T>
     */
    public function withProperty(string $name, mixed $value): self
    {
        $this->properties[$name] = $value;

        return $this;
    }

    /**
     * @return T
     */
    public function build(): object
    {
        $instance = (new ReflectionClass($this->className))->newInstanceWithoutConstructor();

        foreach ($this->properties as $name => $value) {
            $this->findProperty($name)->setValue($instance, $value);
        }

        return $instance;
    }

    /**
     * Private properties of parent classes are invisible to the child's
     * ReflectionClass, so the hierarchy is walked until a class declares it.
     */
    private function findProperty(string $name): ReflectionProperty
    {
        $hierarchy = [];
        $class = new ReflectionClass($this->className);

        while (false !== $class) {
            if ($class->hasProperty($name)) {
                return $class->getProperty($name);
            }

            $hierarchy[] = $class->getName();
            $class = $class->getParentClass();
        }

        throw new ReflectionException(sprintf('Property $%s does not exist in %s.', $name, implode(' > ', $hierarchy)), 1752561608);
    }
}

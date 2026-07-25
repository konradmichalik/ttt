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

namespace KonradMichalik\Ttt\Registry;

use Closure;
use KonradMichalik\Ttt\Attribute\TttAttribute;
use KonradMichalik\Ttt\Handler\AttributeHandler;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function array_merge;
use function array_pop;

/**
 * SandboxRegistry.
 *
 * Collects Terrarium attributes for a given test (class-level first, then
 * method-level), applies them through their handlers and keeps the restorer
 * stack. Restoration runs in LIFO order and is guaranteed to execute every
 * restorer, even if one of them throws.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class SandboxRegistry
{
    /** @var list<Closure(): void> */
    private array $restorers = [];

    /**
     * Attribute lookups run for every single test, so results are memoized
     * per class-level and per method-level target. Caching the resolved
     * instances is safe: Terrarium attributes are readonly DTOs and handlers
     * never mutate them.
     *
     * @var array<string, list<TttAttribute>>
     */
    private array $attributeCache = [];

    /**
     * @param iterable<AttributeHandler> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {}

    /**
     * @param class-string $className
     */
    public function applyFor(string $className, string $methodName): void
    {
        foreach ($this->resolveAttributes($className, $methodName) as $attribute) {
            foreach ($this->handlers as $handler) {
                if ($handler->supports($attribute)) {
                    $this->restorers[] = $handler->apply($attribute);
                }
            }
        }
    }

    /**
     * @throws Throwable The first restorer failure, after ALL restorers ran
     */
    public function restoreAll(): void
    {
        $firstFailure = null;

        while ([] !== $this->restorers) {
            $restorer = array_pop($this->restorers);

            try {
                $restorer();
            } catch (Throwable $throwable) {
                $firstFailure ??= $throwable;
            }
        }

        if (null !== $firstFailure) {
            throw $firstFailure;
        }
    }

    /**
     * @param class-string $className
     *
     * @return list<TttAttribute>
     */
    private function resolveAttributes(string $className, string $methodName): array
    {
        return array_merge(
            $this->attributeCache[$className] ??= self::classAttributes($className),
            $this->attributeCache[$className.'::'.$methodName] ??= self::methodAttributes($className, $methodName),
        );
    }

    /**
     * @param class-string $className
     *
     * @return list<TttAttribute>
     */
    private static function classAttributes(string $className): array
    {
        $attributes = [];

        foreach ((new ReflectionClass($className))->getAttributes(TttAttribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }

    /**
     * @param class-string $className
     *
     * @return list<TttAttribute>
     */
    private static function methodAttributes(string $className, string $methodName): array
    {
        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->hasMethod($methodName)) {
            return [];
        }

        $attributes = [];

        foreach ((new ReflectionMethod($className, $methodName))->getAttributes(TttAttribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        return $attributes;
    }
}

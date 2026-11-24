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
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function array_map;
use function array_merge;
use function array_pop;
use function sprintf;

/**
 * SandboxRegistry.
 *
 * Collects Terrarium attributes for a given test (class-level including
 * parent classes, parents first, then method-level, then those of the
 * current data set), applies them through
 * their handlers and keeps the restorer stack. Restoration runs in LIFO
 * order and is guaranteed to execute every restorer, even if one of them
 * throws.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class SandboxRegistry
{
    /** @var list<Closure(): void> */
    private array $restorers = [];

    /**
     * Attribute lookups run for every single test, so the reflection results
     * are memoized per class-level and per method-level target. Only the
     * ReflectionAttribute objects are cached; instances are created per test,
     * so object arguments (fakes, stubs) are never shared between tests.
     *
     * @var array<string, list<ReflectionAttribute<TttAttribute>>>
     */
    private array $attributeCache = [];

    /**
     * @param iterable<AttributeHandler> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {}

    /**
     * @param class-string       $className
     * @param list<TttAttribute> $dataSetAttributes applied last, so they take precedence
     *
     * @throws LogicException When an attribute has no handler
     * @throws Throwable      The handler failure, after already applied attributes were rolled back
     */
    public function applyFor(string $className, string $methodName, array $dataSetAttributes = []): void
    {
        try {
            $attributes = array_map(
                static fn (ReflectionAttribute $attribute): TttAttribute => $attribute->newInstance(),
                $this->resolveAttributes($className, $methodName),
            );

            foreach ([...$attributes, ...$dataSetAttributes] as $attribute) {
                $handled = false;

                foreach ($this->handlers as $handler) {
                    if ($handler->supports($attribute)) {
                        $handled = true;
                        $this->restorers[] = $handler->apply($attribute);
                    }
                }

                if (!$handled) {
                    throw new LogicException(sprintf('No handler supports the attribute "%s". Register a handler for it via the "handlers" parameter of the Terrarium extension.', $attribute::class), 1752561602);
                }
            }
        } catch (Throwable $throwable) {
            try {
                $this->restoreAll();
            } catch (Throwable) {
                // The apply failure is the root cause and takes precedence.
            }

            throw $throwable;
        }
    }

    /**
     * @param class-string $className
     */
    public function hasAttributesFor(string $className, string $methodName): bool
    {
        return [] !== $this->resolveAttributes($className, $methodName);
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
     * @return list<ReflectionAttribute<TttAttribute>>
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
     * @return list<ReflectionAttribute<TttAttribute>>
     */
    private static function classAttributes(string $className): array
    {
        $attributes = [];
        $reflectionClass = new ReflectionClass($className);

        // Parents first, so attributes of the concrete test class win.
        while (false !== $reflectionClass) {
            $attributes = [
                ...$reflectionClass->getAttributes(TttAttribute::class, ReflectionAttribute::IS_INSTANCEOF),
                ...$attributes,
            ];
            $reflectionClass = $reflectionClass->getParentClass();
        }

        return $attributes;
    }

    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute<TttAttribute>>
     */
    private static function methodAttributes(string $className, string $methodName): array
    {
        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->hasMethod($methodName)) {
            return [];
        }

        return (new ReflectionMethod($className, $methodName))->getAttributes(TttAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
    }
}

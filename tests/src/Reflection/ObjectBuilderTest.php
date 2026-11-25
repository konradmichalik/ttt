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

namespace KonradMichalik\Ttt\Tests\Reflection;

use KonradMichalik\Ttt\Reflection\ObjectBuilder;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;
use stdClass;

/**
 * ObjectBuilderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ObjectBuilder::class)]
final class ObjectBuilderTest extends TestCase
{
    #[Test]
    public function buildsInstanceWithoutCallingConstructor(): void
    {
        $instance = ObjectBuilder::withoutConstructor(ObjectBuilderFixture::class)->build();

        // The constructor gives $name a default value of 'default'; it
        // staying uninitialized proves the constructor was never invoked.
        self::assertFalse((new ReflectionProperty($instance, 'name'))->isInitialized($instance));
    }

    #[Test]
    public function setsAPrivateReadonlyProperty(): void
    {
        $dependency = new stdClass();

        $instance = ObjectBuilder::withoutConstructor(ObjectBuilderFixture::class)
            ->withProperty('dependency', $dependency)
            ->build();

        self::assertSame($dependency, $instance->getDependency());
    }

    #[Test]
    public function setsMultipleProperties(): void
    {
        $instance = ObjectBuilder::withoutConstructor(ObjectBuilderFixture::class)
            ->withProperty('dependency', new stdClass())
            ->withProperty('name', 'configured')
            ->build();

        self::assertSame('configured', $instance->getName());
    }

    #[Test]
    public function buildCanBeCalledMultipleTimesProducingIndependentInstances(): void
    {
        $builder = ObjectBuilder::withoutConstructor(ObjectBuilderFixture::class)
            ->withProperty('dependency', new stdClass())
            ->withProperty('name', 'shared');

        $first = $builder->build();
        $second = $builder->build();

        self::assertNotSame($first, $second);
        self::assertSame('shared', $second->getName());
    }

    #[Test]
    public function setsPrivatePropertiesOfParentClasses(): void
    {
        $dependency = new stdClass();

        $instance = ObjectBuilder::withoutConstructor(ObjectBuilderChildFixture::class)
            ->withProperty('dependency', $dependency)
            ->build();

        self::assertSame($dependency, $instance->dependency());
    }

    #[Test]
    public function namesTheWholeHierarchyWhenThePropertyDoesNotExist(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage(ObjectBuilderChildFixture::class.' > '.ObjectBuilderParentFixture::class);

        ObjectBuilder::withoutConstructor(ObjectBuilderChildFixture::class)
            ->withProperty('missing', 1)
            ->build();
    }
}

/**
 * ObjectBuilderParentFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
abstract class ObjectBuilderParentFixture
{
    // Written through reflection by ObjectBuilder only.
    // @phpstan-ignore property.uninitializedReadonly, property.onlyRead
    private readonly stdClass $dependency;

    final public function dependency(): stdClass
    {
        return $this->dependency;
    }
}

/**
 * ObjectBuilderChildFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ObjectBuilderChildFixture extends ObjectBuilderParentFixture {}

/**
 * ObjectBuilderFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ObjectBuilderFixture
{
    public function __construct(
        private stdClass $dependency,
        private string $name = 'default',
    ) {}

    public function getDependency(): stdClass
    {
        return $this->dependency;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

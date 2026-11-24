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

namespace KonradMichalik\Ttt\Tests\Registry;

use Closure;
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithEnvVar, WithGlobal, WithTypo3ConfVars};
use KonradMichalik\Ttt\Handler\{ConfVarsHandler, EnvVarHandler};
use KonradMichalik\Ttt\Registry\SandboxRegistry;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * SandboxRegistryTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(SandboxRegistry::class)]
final class SandboxRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
        putenv('TTT_REGISTRY_VAR');
        unset($_ENV['TTT_REGISTRY_VAR'], $_SERVER['TTT_REGISTRY_VAR']);
    }

    #[Test]
    public function appliesClassLevelBeforeMethodLevelAttributes(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler()]);

        $registry->applyFor(AnnotatedFixture::class, 'annotatedMethod');

        self::assertSame('method', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['SYS']['fromClass']);

        $registry->restoreAll();

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }

    #[Test]
    public function appliesRepeatedAndMixedAttributes(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler(), new EnvVarHandler()]);

        $registry->applyFor(AnnotatedFixture::class, 'mixedMethod');

        self::assertSame('on', getenv('TTT_REGISTRY_VAR'));
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['SYS']['fromClass']);

        $registry->restoreAll();

        self::assertFalse(getenv('TTT_REGISTRY_VAR'));
    }

    #[Test]
    public function ignoresMethodAttributesWhenTheMethodDoesNotExist(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler()]);

        $registry->applyFor(AnnotatedFixture::class, 'methodThatDoesNotExist');

        // Only the class-level attribute applies; the unknown method contributes nothing.
        self::assertSame('class', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);

        $registry->restoreAll();

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }

    #[Test]
    public function appliesDataSetAttributesAfterClassAndMethodLevelAttributes(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler()]);

        $registry->applyFor(AnnotatedFixture::class, 'annotatedMethod', [
            new WithTypo3ConfVars(['SYS' => ['sitename' => 'data set']]),
        ]);

        self::assertSame('data set', $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['SYS']['fromClass']);

        $registry->restoreAll();

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }

    #[Test]
    public function hasAttributesForReportsClassAndMethodLevelAttributesWithoutApplyingThem(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler()]);

        self::assertTrue($registry->hasAttributesFor(AnnotatedFixture::class, 'methodThatDoesNotExist'));
        self::assertTrue($registry->hasAttributesFor(PlainFixture::class, 'annotatedMethod'));
        self::assertFalse($registry->hasAttributesFor(PlainFixture::class, 'plainMethod'));
        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }

    #[Test]
    public function createsFreshAttributeInstancesForEveryApplication(): void
    {
        $seen = [];
        $registry = new SandboxRegistry([
            new CallbackHandler(static fn () => null, static function (TttAttribute $attribute) use (&$seen): void {
                $seen[] = $attribute;
            }),
        ]);

        $registry->applyFor(ObjectArgumentFixture::class, 'method');
        $registry->restoreAll();
        $registry->applyFor(ObjectArgumentFixture::class, 'method');
        $registry->restoreAll();

        self::assertCount(2, $seen);
        self::assertInstanceOf(WithGlobal::class, $seen[0]);
        self::assertInstanceOf(WithGlobal::class, $seen[1]);
        self::assertNotSame($seen[0], $seen[1]);
        self::assertNotSame($seen[0]->value, $seen[1]->value);
    }

    #[Test]
    public function rollsBackAlreadyAppliedAttributesWhenAHandlerFails(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler(), new FailingHandler()]);

        try {
            $registry->applyFor(FailingFixture::class, 'method');
            self::fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame(1752561601, $exception->getCode());
        }

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
        $registry->restoreAll();
    }

    #[Test]
    public function restoreAllRunsEveryRestorerEvenIfOneThrows(): void
    {
        $order = [];
        $registry = new SandboxRegistry([
            new CallbackHandler(static function () use (&$order): void {
                $order[] = 'first';
            }),
            new CallbackHandler(static function (): void {
                throw new RuntimeException('restore failed', 1752561600);
            }),
        ]);

        $registry->applyFor(AnnotatedFixture::class, 'annotatedMethod');

        try {
            $registry->restoreAll();
            self::fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
        }

        self::assertNotEmpty($order, 'Non-throwing restorers must still run.');
    }
}

/**
 * AnnotatedFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[WithTypo3ConfVars(['SYS' => ['fromClass' => true, 'sitename' => 'class']])]
final class AnnotatedFixture
{
    #[WithTypo3ConfVars(['SYS' => ['sitename' => 'method']])]
    public function annotatedMethod(): void {}

    #[WithEnvVar('TTT_REGISTRY_VAR', 'on')]
    public function mixedMethod(): void {}
}

/**
 * PlainFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class PlainFixture
{
    #[WithEnvVar('TTT_REGISTRY_VAR', 'on')]
    public function annotatedMethod(): void {}

    public function plainMethod(): void {}
}

/**
 * ObjectArgumentFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ObjectArgumentFixture
{
    #[WithGlobal('ttt_shared', new stdClass())]
    public function method(): void {}
}

/**
 * FailingFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class FailingFixture
{
    #[WithTypo3ConfVars(['SYS' => ['sitename' => 'applied']])]
    #[WithEnvVar('TTT_REGISTRY_VAR', 'on')]
    public function method(): void {}
}

/**
 * FailingHandler.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class FailingHandler implements \KonradMichalik\Ttt\Handler\AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithEnvVar;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        throw new RuntimeException('apply failed', 1752561601);
    }
}

/**
 * CallbackHandler.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class CallbackHandler implements \KonradMichalik\Ttt\Handler\AttributeHandler
{
    /**
     * @param (Closure(TttAttribute): void)|null $onApply
     */
    public function __construct(
        private Closure $restorer,
        private ?Closure $onApply = null,
    ) {}

    public function supports(TttAttribute $attribute): bool
    {
        return true;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        if (null !== $this->onApply) {
            ($this->onApply)($attribute);
        }

        return $this->restorer;
    }
}

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

use KonradMichalik\Ttt\Attribute\{WithEnvVar, WithTypo3ConfVars};
use KonradMichalik\Ttt\Handler\{ConfVarsHandler, EnvVarHandler};
use KonradMichalik\Ttt\Registry\SandboxRegistry;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
        \putenv('TTT_REGISTRY_VAR');
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

        self::assertSame('on', \getenv('TTT_REGISTRY_VAR'));
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['SYS']['fromClass']);

        $registry->restoreAll();

        self::assertFalse(\getenv('TTT_REGISTRY_VAR'));
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
 * CallbackHandler.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class CallbackHandler implements \KonradMichalik\Ttt\Handler\AttributeHandler
{
    public function __construct(
        private \Closure $restorer,
    ) {}

    public function supports(\KonradMichalik\Ttt\Attribute\TttAttribute $attribute): bool
    {
        return true;
    }

    public function apply(\KonradMichalik\Ttt\Attribute\TttAttribute $attribute): \Closure
    {
        return $this->restorer;
    }
}

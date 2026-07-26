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

namespace KonradMichalik\Ttt\Tests\Traits;

use Closure;
use KonradMichalik\Ttt\Attribute\WithEnvironment;
use KonradMichalik\Ttt\Handler\EnvironmentHandler;
use KonradMichalik\Ttt\Traits\ApplicationContextSwitcher;
use PHPUnit\Framework\Attributes\{CoversTrait, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TYPO3\CMS\Core\Core\Environment;

/**
 * ApplicationContextSwitcherTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversTrait(ApplicationContextSwitcher::class)]
final class ApplicationContextSwitcherTest extends TestCase
{
    use ApplicationContextSwitcher;

    /** @var Closure(): void */
    private Closure $restoreEnvironment;

    protected function setUp(): void
    {
        $this->restoreEnvironment = (new EnvironmentHandler())->apply(new WithEnvironment(context: 'Testing'));
    }

    protected function tearDown(): void
    {
        ($this->restoreEnvironment)();
    }

    #[Test]
    public function runsCallbackInsideTheGivenContextAndRestoresAfterwards(): void
    {
        self::assertSame('Testing', (string) Environment::getContext());

        $observed = null;
        $this->inApplicationContext('Development', static function () use (&$observed): void {
            $observed = (string) Environment::getContext();
        });

        self::assertSame('Development', $observed);
        self::assertSame('Testing', (string) Environment::getContext());
    }

    #[Test]
    public function restoresContextEvenWhenTheCallbackThrows(): void
    {
        try {
            $this->inApplicationContext('Development', static function (): void {
                throw new RuntimeException('boom', 1752561700);
            });
            self::fail('Expected exception was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertSame('Testing', (string) Environment::getContext());
    }
}

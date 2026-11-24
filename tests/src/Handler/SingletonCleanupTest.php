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

namespace KonradMichalik\Ttt\Tests\Handler;

use Closure;
use Generator;
use KonradMichalik\Ttt\Attribute\{FreezeTime, TttAttribute, WithBackendUser, WithCache, WithFrontendUser};
use KonradMichalik\Ttt\Handler\{AttributeHandler, BackendUserHandler, CacheHandler, FreezeTimeHandler, FrontendUserHandler, SingletonCleanup};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SingletonCleanupTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(SingletonCleanup::class)]
final class SingletonCleanupTest extends TestCase
{
    /** @var array<class-string, SingletonInterface> */
    private array $singletons = [];

    protected function setUp(): void
    {
        $this->singletons = GeneralUtility::getSingletonInstances();
        GeneralUtility::resetSingletonInstances([]);
    }

    protected function tearDown(): void
    {
        GeneralUtility::resetSingletonInstances($this->singletons);
        unset($GLOBALS['BE_USER'], $GLOBALS['FE_USER']);
    }

    /**
     * @return Generator<string, array{AttributeHandler, Closure(): TttAttribute}>
     */
    // Attributes are wrapped in closures: Terrarium itself would apply attribute instances found in a data set.
    public static function handlersCreatingASingleton(): Generator
    {
        yield 'WithBackendUser' => [new BackendUserHandler(), static fn () => new WithBackendUser()];
        yield 'WithFrontendUser' => [new FrontendUserHandler(), static fn () => new WithFrontendUser()];
        yield 'FreezeTime' => [new FreezeTimeHandler(), static fn () => new FreezeTime('2026-07-14T12:00:00Z')];
        yield 'WithCache' => [new CacheHandler(), static fn () => new WithCache('ttt_cleanup')];
    }

    #[Test]
    #[DataProvider('handlersCreatingASingleton')]
    public function handlersDropSingletonsTheyCreated(AttributeHandler $handler, Closure $attribute): void
    {
        self::assertSame([], GeneralUtility::getSingletonInstances());

        $restore = $handler->apply($attribute());
        $restore();

        self::assertSame([], GeneralUtility::getSingletonInstances());
    }

    #[Test]
    public function keepsSingletonsThatExistedBefore(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);

        $restore = (new BackendUserHandler())->apply(new WithBackendUser());
        $restore();

        self::assertSame($context, GeneralUtility::makeInstance(Context::class));
        self::assertArrayNotHasKey(CacheManager::class, GeneralUtility::getSingletonInstances());
    }
}

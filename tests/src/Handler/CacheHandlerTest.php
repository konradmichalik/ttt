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

use KonradMichalik\Ttt\Attribute\WithCache;
use KonradMichalik\Ttt\Handler\CacheHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use TYPO3\CMS\Core\Cache\Backend\{NullBackend, TransientMemoryBackend};
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * CacheHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(CacheHandler::class)]
#[CoversClass(WithCache::class)]
final class CacheHandlerTest extends TestCase
{
    private CacheManager $cacheManager;

    protected function setUp(): void
    {
        $this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    #[Test]
    public function registersRuntimeCacheByDefault(): void
    {
        $restore = (new CacheHandler())->apply(new WithCache());

        self::assertTrue($this->cacheManager->hasCache('runtime'));
        self::assertInstanceOf(VariableFrontend::class, $this->cacheManager->getCache('runtime'));

        $restore();
    }

    #[Test]
    public function registersCacheWithGivenIdentifierFrontendAndBackend(): void
    {
        $restore = (new CacheHandler())->apply(new WithCache('pages', VariableFrontend::class, NullBackend::class));

        self::assertTrue($this->cacheManager->hasCache('pages'));

        $restore();
    }

    #[Test]
    public function restoresPreviousCacheConfigurationForTheSameIdentifier(): void
    {
        $this->cacheManager->setCacheConfigurations([
            'runtime' => [
                'frontend' => VariableFrontend::class,
                'backend' => NullBackend::class,
            ],
        ]);

        $restore = (new CacheHandler())->apply(new WithCache());

        self::assertInstanceOf(TransientMemoryBackend::class, self::backendOf($this->cacheManager->getCache('runtime')));

        $restore();

        self::assertInstanceOf(NullBackend::class, self::backendOf($this->cacheManager->getCache('runtime')));
    }

    #[Test]
    public function clearsCacheInstanceCreatedWhileAttributeWasApplied(): void
    {
        $restore = (new CacheHandler())->apply(new WithCache());

        $this->cacheManager->getCache('runtime');

        $restore();

        self::assertFalse($this->cacheManager->hasCache('runtime'));
    }

    #[Test]
    public function preservesUnrelatedCacheConfigurationsWhileApplied(): void
    {
        $this->cacheManager->setCacheConfigurations([
            'pages' => [
                'frontend' => VariableFrontend::class,
                'backend' => NullBackend::class,
            ],
        ]);

        $restore = (new CacheHandler())->apply(new WithCache());

        self::assertTrue($this->cacheManager->hasCache('pages'));
        self::assertTrue($this->cacheManager->hasCache('runtime'));

        $restore();

        self::assertTrue($this->cacheManager->hasCache('pages'));
        self::assertFalse($this->cacheManager->hasCache('runtime'));
    }

    private static function backendOf(mixed $cache): mixed
    {
        $property = new ReflectionProperty($cache, 'backend');

        return $property->getValue($cache);
    }
}

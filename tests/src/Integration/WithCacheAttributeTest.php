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

namespace KonradMichalik\Ttt\Tests\Integration;

use KonradMichalik\Ttt\Attribute\WithCache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * WithCacheAttributeTest.
 *
 * End-to-end proof that the TttExtension (registered in phpunit.xml) applies
 * #[WithCache] so GeneralUtility::xml2array() works without a manual
 * CacheManager bootstrap, and restores the "runtime" cache registration
 * afterwards. The unannotated test verifies restoration independent of
 * execution order, as the process-wide baseline has no "runtime" cache.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class WithCacheAttributeTest extends TestCase
{
    #[Test]
    #[WithCache]
    public function xml2arrayWorksWithoutManualCacheManagerSetup(): void
    {
        $result = GeneralUtility::xml2array('<numIndex index="0">value</numIndex>');

        self::assertSame('value', $result);
    }

    #[Test]
    public function runtimeCacheIsNotRegisteredWithoutTheAttribute(): void
    {
        $this->expectException(NoSuchCacheException::class);

        GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }
}

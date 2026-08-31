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

namespace KonradMichalik\Ttt\Handler;

use Closure;
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithCache};
use ReflectionProperty;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function assert;

/**
 * CacheHandler.
 *
 * Applies WithCache: snapshots the CacheManager singleton's current cache
 * configurations, already-instantiated cache frontends and cache groups,
 * registers the given configuration via CacheManager::setCacheConfigurations()
 * and restores the exact previous state afterwards.
 *
 * setCacheConfigurations() replaces the entire configuration array on every
 * call, so the previous configurations are merged back in with the new
 * identifier taking precedence rather than passed as-is. Any frontend
 * already instantiated for that identifier is dropped from the live cache
 * map so getCache() rebuilds it against the new configuration instead of
 * returning the stale instance; createCache() appends to cacheGroups as a
 * side effect of that rebuild, so cacheGroups is restored alongside it.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class CacheHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithCache;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithCache);

        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);

        $configurationsProperty = new ReflectionProperty(CacheManager::class, 'cacheConfigurations');
        $cachesProperty = new ReflectionProperty(CacheManager::class, 'caches');
        $cacheGroupsProperty = new ReflectionProperty(CacheManager::class, 'cacheGroups');

        $configurationsBefore = $configurationsProperty->getValue($cacheManager);
        $cachesBefore = $cachesProperty->getValue($cacheManager);
        $cacheGroupsBefore = $cacheGroupsProperty->getValue($cacheManager);

        $configurations = $configurationsBefore;
        $configurations[$attribute->identifier] = [
            'frontend' => $attribute->frontend,
            'backend' => $attribute->backend,
        ];
        $cacheManager->setCacheConfigurations($configurations);

        $caches = $cachesBefore;
        unset($caches[$attribute->identifier]);
        $cachesProperty->setValue($cacheManager, $caches);

        return static function () use ($cacheManager, $configurationsProperty, $cachesProperty, $cacheGroupsProperty, $configurationsBefore, $cachesBefore, $cacheGroupsBefore): void {
            $configurationsProperty->setValue($cacheManager, $configurationsBefore);
            $cachesProperty->setValue($cacheManager, $cachesBefore);
            $cacheGroupsProperty->setValue($cacheManager, $cacheGroupsBefore);
        };
    }
}

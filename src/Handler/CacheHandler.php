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
 * configurations and any already-instantiated cache frontends, registers
 * the given configuration via CacheManager::setCacheConfigurations() and
 * restores the exact previous state afterwards.
 *
 * setCacheConfigurations() replaces the entire configuration array on every
 * call, so the previous configurations are merged back in with the new
 * identifier taking precedence rather than passed as-is.
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

        $configurationsBefore = $configurationsProperty->getValue($cacheManager);
        $cachesBefore = $cachesProperty->getValue($cacheManager);

        $configurations = $configurationsBefore;
        $configurations[$attribute->identifier] = [
            'frontend' => $attribute->frontend,
            'backend' => $attribute->backend,
        ];
        $cacheManager->setCacheConfigurations($configurations);

        return static function () use ($cacheManager, $configurationsProperty, $cachesProperty, $configurationsBefore, $cachesBefore): void {
            $configurationsProperty->setValue($cacheManager, $configurationsBefore);
            $cachesProperty->setValue($cacheManager, $cachesBefore);
        };
    }
}

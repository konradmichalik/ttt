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

namespace KonradMichalik\Ttt\Attribute;

use Attribute;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;

/**
 * WithCache.
 *
 * Registers a cache configuration via CacheManager::setCacheConfigurations()
 * for the duration of one test and restores the previous configuration for
 * that identifier afterwards, mirroring WithSingleton's snapshot/restore
 * semantics. Defaults to the "runtime" cache (VariableFrontend backed by
 * TransientMemoryBackend) needed by GeneralUtility::xml2array(), which a
 * plain unit test never has registered since it doesn't bootstrap TYPO3's
 * full cache configuration.
 *
 * <code>
 * #[WithCache]
 * </code>
 *
 * <code>
 * #[WithCache('pages', backend: NullBackend::class)]
 * </code>
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithCache implements TttAttribute
{
    /**
     * @param class-string $frontend
     * @param class-string $backend
     */
    public function __construct(
        public string $identifier = 'runtime',
        public string $frontend = VariableFrontend::class,
        public string $backend = TransientMemoryBackend::class,
    ) {}
}

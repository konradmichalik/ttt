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

/**
 * WithSingleton.
 *
 * Registers a singleton instance via GeneralUtility::setSingletonInstance()
 * for the duration of one test and restores the previous singleton map
 * afterwards. The instance may be given as an object (PHP 8.1+ allows "new"
 * in attribute arguments) or as a class-string that will be instantiated
 * without constructor arguments.
 *
 * <code>
 * #[WithSingleton(CacheManager::class, new NullCacheManager())]
 * </code>
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithSingleton implements TttAttribute
{
    /**
     * @param class-string        $className
     * @param object|class-string $instance
     */
    public function __construct(
        public string $className,
        public object|string $instance,
    ) {}
}

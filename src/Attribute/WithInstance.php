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
 * WithInstance.
 *
 * Queues a fake for GeneralUtility::makeInstance($className) via
 * GeneralUtility::addInstance() - the FIFO queue for non-singleton fakes,
 * as opposed to WithSingleton's setSingletonInstance(). The queued instance
 * is consumed by the first matching makeInstance() call, exactly per normal
 * addInstance() semantics.
 *
 * Restore-only scope: any instance still queued at test end (never consumed)
 * is purged so it cannot leak into the next test. This does NOT assert that
 * the instance was actually consumed - that would require a post-condition
 * hook, which this attribute does not provide.
 *
 * <code>
 * #[WithInstance(MyMailer::class, new FakeMailer())]
 * </code>
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithInstance implements TttAttribute
{
    /**
     * @param class-string $className
     */
    public function __construct(
        public string $className,
        public object $instance,
    ) {}
}

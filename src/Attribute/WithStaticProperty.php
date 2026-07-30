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
 * WithStaticProperty.
 *
 * Generic escape hatch for sandboxing an arbitrary static property via
 * reflection - equivalent to pytest's monkeypatch.setattr for the long tail
 * of static state that doesn't have a dedicated attribute yet. Prefer a
 * dedicated attribute where one exists; reach for this only when none does.
 *
 * Works for private/protected statics via ReflectionProperty. Two cases
 * fail loudly instead of silently misbehaving:
 * - a readonly static property cannot be sandboxed (PHP does not allow
 *   assigning to it a second time);
 * - a property that is not yet initialized cannot be sandboxed either -
 *   PHP's reflection API has no way to revert a typed property back to the
 *   uninitialized state, so restoring it to `null` would silently change
 *   its behavior (a nullable property explicitly set to null differs from
 *   one that was never initialized: reading the latter throws).
 *
 * <code>
 * #[WithStaticProperty(GeneralUtility::class, 'indpEnvCache', [])]
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithStaticProperty implements TttAttribute
{
    /**
     * @param class-string $className
     */
    public function __construct(
        public string $className,
        public string $propertyName,
        public mixed $value,
    ) {}
}

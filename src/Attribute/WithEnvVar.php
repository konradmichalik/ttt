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
 * WithEnvVar.
 *
 * Sets an environment variable (putenv(), $_ENV and $_SERVER) for the duration
 * of a single test and restores the previous state afterwards. Note that
 * getenv() calls evaluated at cache-build time (e.g. in ext_localconf.php)
 * are NOT affected - this attribute targets per-request evaluations only.
 *
 * <code>
 * #[WithEnvVar('TYPO3_REQUEST_PROFILER_FORCE', '1')]
 * public function honoursForceFlag(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithEnvVar implements TttAttribute
{
    public function __construct(
        public string $name,
        public string $value,
    ) {}
}

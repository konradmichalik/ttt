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
 * WithTypo3ConfVars.
 *
 * Deep-merges the given configuration into $GLOBALS['TYPO3_CONF_VARS'] before
 * the test is prepared and guarantees a full restore afterwards - regardless
 * of the test outcome. Class-level attributes are applied before method-level
 * attributes; later attributes win on conflicting keys.
 *
 * <code>
 * #[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => []]]])]
 * public function resolvesConfiguration(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithTypo3ConfVars implements TttAttribute
{
    public function __construct(
        /** @var array<string, mixed> */
        public array $configuration,
    ) {}
}

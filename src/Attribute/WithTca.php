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
 * WithTca.
 *
 * Deep-merges the given configuration into $GLOBALS['TCA'][$table] before
 * the test is prepared and guarantees a full restore afterwards - regardless
 * of the test outcome. Class-level attributes are applied before method-level
 * attributes; later attributes win on conflicting keys. Same merge and
 * sentinel semantics as WithTypo3ConfVars - use Typo3ConfVarsSentinel::Unset
 * to explicitly clear a key that would otherwise survive the merge.
 *
 * <code>
 * #[WithTca('tt_content', ['columns' => ['tx_myext_field' => ['config' => ['type' => 'input']]]])]
 * #[WithTca('pages', ['ctrl' => ['label' => 'nav_title']])]
 * public function resolvesConfiguration(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithTca implements TttAttribute
{
    public function __construct(
        public string $table,
        /** @var array<string, mixed> */
        public array $configuration,
    ) {}
}

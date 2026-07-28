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

/**
 * Typo3ConfVarsSentinel.
 *
 * Marker value for WithTypo3ConfVars::$configuration: the deep merge can't
 * override an existing array subtree to empty (merging an empty array onto
 * an existing one is a no-op), so this sentinel declaratively clears a
 * config path instead - e.g. to simulate the "not configured" case for a
 * key set by a class-level attribute.
 *
 * <code>
 * #[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => Typo3ConfVarsSentinel::Unset]]])]
 * public function behavesAsUnconfigured(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
enum Typo3ConfVarsSentinel
{
    case Unset;
}

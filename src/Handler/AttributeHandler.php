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
use KonradMichalik\Ttt\Attribute\TttAttribute;

/**
 * AttributeHandler.
 *
 * Applies the state described by a Terrarium attribute and returns a restorer
 * closure that reverts the state exactly. Handlers must be stateless - all
 * captured state belongs into the returned closure.
 *
 * Public API: covered by this package's backward-compatibility promise.
 * Consuming extensions register their own implementations via the
 * TttExtension "handlers" bootstrap parameter (see the README's "Extending"
 * section).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
interface AttributeHandler
{
    public function supports(TttAttribute $attribute): bool;

    /**
     * @return Closure(): void Restorer reverting the applied state
     */
    public function apply(TttAttribute $attribute): Closure;
}

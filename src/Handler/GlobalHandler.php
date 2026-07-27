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
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithGlobal};

use function array_key_exists;
use function assert;

/**
 * GlobalHandler.
 *
 * Applies WithGlobal: sets an arbitrary $GLOBALS entry and restores the
 * previous value (including a previously unset key) afterwards.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class GlobalHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithGlobal;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithGlobal);

        $key = $attribute->key;
        $existed = array_key_exists($key, $GLOBALS);
        $previous = $GLOBALS[$key] ?? null;

        $GLOBALS[$key] = $attribute->value;

        return static function () use ($key, $existed, $previous): void {
            if ($existed) {
                $GLOBALS[$key] = $previous;
            } else {
                unset($GLOBALS[$key]);
            }
        };
    }
}

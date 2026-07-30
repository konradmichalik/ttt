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
use KonradMichalik\Ttt\Attribute\{InTimeZone, TttAttribute};
use RuntimeException;

use function assert;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function sprintf;

/**
 * InTimeZoneHandler.
 *
 * Applies InTimeZone: sets the default timezone and restores the previous
 * one afterwards. Fails loudly for an invalid timezone identifier instead of
 * silently leaving the previous timezone in place, which is what
 * date_default_timezone_set() does on failure (it returns false and raises
 * a warning rather than throwing).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class InTimeZoneHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof InTimeZone;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof InTimeZone);

        $previous = date_default_timezone_get();

        if (!@date_default_timezone_set($attribute->timeZone)) {
            throw new RuntimeException(sprintf('InTimeZone: "%s" is not a valid timezone identifier.', $attribute->timeZone), 1753900201);
        }

        return static function () use ($previous): void {
            date_default_timezone_set($previous);
        };
    }
}

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
use KonradMichalik\Ttt\Attribute\{InLocale, TttAttribute};
use RuntimeException;

use function assert;
use function setlocale;
use function sprintf;

/**
 * InLocaleHandler.
 *
 * Applies InLocale: sets the locale for the given category and restores the
 * previous one afterwards. The current locale is read via setlocale($category,
 * '0') - the documented "query without changing" form - rather than
 * setlocale($category, null), which is not a valid overload. Fails loudly
 * when the requested locale is unavailable in this environment (a common gap
 * in CI containers) instead of silently leaving the previous locale in
 * place, which is what setlocale() does on failure (it returns false rather
 * than throwing).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class InLocaleHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof InLocale;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof InLocale);

        $previous = setlocale($attribute->category, '0');

        if (false === $previous) {
            // Defensive: querying a valid LC_* category constant with '0'
            // does not fail on any platform PHP itself supports; this
            // guards a case that isn't realistically reachable in practice.
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('InLocale: unable to read the current locale for category %d.', $attribute->category), 1753900202);
            // @codeCoverageIgnoreEnd
        }

        if (false === @setlocale($attribute->category, $attribute->locale)) {
            throw new RuntimeException(sprintf('InLocale: "%s" is not available for category %d in this environment.', $attribute->locale, $attribute->category), 1753900203);
        }

        return static function () use ($attribute, $previous): void {
            setlocale($attribute->category, $previous);
        };
    }
}

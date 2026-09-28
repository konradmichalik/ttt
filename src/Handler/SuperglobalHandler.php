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
use InvalidArgumentException;
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithSuperglobal};

use function array_key_exists;
use function assert;
use function in_array;
use function sprintf;

/**
 * SuperglobalHandler.
 *
 * Applies WithSuperglobal: sets a single key of a PHP superglobal and
 * restores the previous value (including a previously unset key) afterwards.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class SuperglobalHandler implements AttributeHandler
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_SUPERGLOBALS = ['_SERVER', '_GET', '_POST', '_ENV'];

    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithSuperglobal;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithSuperglobal);

        $superglobal = $attribute->superglobal;

        if (!in_array($superglobal, self::SUPPORTED_SUPERGLOBALS, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported superglobal "%s".', $superglobal), 1758999601);
        }

        $key = $attribute->key;
        $existed = array_key_exists($key, $GLOBALS[$superglobal]);
        $previous = $GLOBALS[$superglobal][$key] ?? null;

        if (null === $attribute->value) {
            unset($GLOBALS[$superglobal][$key]);
        } else {
            $GLOBALS[$superglobal][$key] = $attribute->value;
        }

        return static function () use ($superglobal, $key, $existed, $previous): void {
            if ($existed) {
                $GLOBALS[$superglobal][$key] = $previous;
            } else {
                unset($GLOBALS[$superglobal][$key]);
            }
        };
    }
}

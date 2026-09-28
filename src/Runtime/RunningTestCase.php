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

namespace KonradMichalik\Ttt\Runtime;

use PHPUnit\Framework\TestCase;

use function debug_backtrace;

/**
 * RunningTestCase.
 *
 * PHPUnit's events only carry value objects describing the test, never the
 * TestCase instance itself. While Terrarium applies attributes, the running
 * TestCase is always somewhere up the call stack (PHPUnit emits
 * Test\Prepared from inside TestCase::runBare(), and forwards isolated
 * events from inside TestCase::run()), which is where this looks it up.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class RunningTestCase
{
    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    public static function find(): ?TestCase
    {
        foreach (debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT | \DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $object = $frame['object'] ?? null;

            if ($object instanceof TestCase) {
                return $object;
            }
        }

        // Defensive: Terrarium only calls this while PHPUnit is running a
        // test, which always has its TestCase instance on the call stack.
        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }
}

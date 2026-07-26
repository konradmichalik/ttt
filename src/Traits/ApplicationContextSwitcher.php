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

namespace KonradMichalik\Ttt\Traits;

use KonradMichalik\Ttt\Attribute\InApplicationContext;
use KonradMichalik\Ttt\Handler\ApplicationContextHandler;

/**
 * ApplicationContextSwitcher.
 *
 * Imperative alternative to the InApplicationContext attribute for tests that
 * need to switch the TYPO3 application context for a scoped section *within* a
 * single test. The previous context is restored afterwards, even if the
 * callback throws. Requires an initialized Environment (typo3/cms-core).
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
trait ApplicationContextSwitcher
{
    /**
     * @param callable(): void $callback
     */
    protected function inApplicationContext(string $context, callable $callback): void
    {
        $restore = (new ApplicationContextHandler())->apply(new InApplicationContext($context));

        try {
            $callback();
        } finally {
            $restore();
        }
    }
}

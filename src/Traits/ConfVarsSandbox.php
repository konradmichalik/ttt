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

use Closure;
use KonradMichalik\Ttt\Attribute\WithTypo3ConfVars;
use KonradMichalik\Ttt\Handler\ConfVarsHandler;

/**
 * ConfVarsSandbox.
 *
 * Imperative alternative to the WithTypo3ConfVars attribute for tests that
 * need to manipulate TYPO3_CONF_VARS mid-test or do not run with the
 * TttExtension enabled. Call restoreTypo3ConfVars() in tearDown().
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
trait ConfVarsSandbox
{
    /** @var list<Closure(): void> */
    private array $confVarsRestorers = [];

    /**
     * @param array<string, mixed> $configuration
     */
    protected function setTypo3ConfVars(array $configuration): void
    {
        $this->confVarsRestorers[] = (new ConfVarsHandler())->apply(
            new WithTypo3ConfVars($configuration),
        );
    }

    protected function restoreTypo3ConfVars(): void
    {
        while ([] !== $this->confVarsRestorers) {
            $restorer = array_pop($this->confVarsRestorers);
            $restorer();
        }
    }
}

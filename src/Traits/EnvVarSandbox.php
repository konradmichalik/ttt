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
use KonradMichalik\Ttt\Attribute\WithEnvVar;
use KonradMichalik\Ttt\Handler\EnvVarHandler;

/**
 * EnvVarSandbox.
 *
 * Imperative alternative to the WithEnvVar attribute for tests that need to
 * manipulate environment variables mid-test or do not run with the
 * TttExtension enabled. Call restoreEnvVars() in tearDown().
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
trait EnvVarSandbox
{
    /** @var list<Closure(): void> */
    private array $envVarRestorers = [];

    protected function setEnvVar(string $name, string $value): void
    {
        $this->envVarRestorers[] = (new EnvVarHandler())->apply(
            new WithEnvVar($name, $value),
        );
    }

    protected function restoreEnvVars(): void
    {
        while ([] !== $this->envVarRestorers) {
            $restorer = array_pop($this->envVarRestorers);
            $restorer();
        }
    }
}

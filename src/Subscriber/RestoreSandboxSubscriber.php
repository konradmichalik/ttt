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

namespace KonradMichalik\Ttt\Subscriber;

use KonradMichalik\Ttt\Registry\SandboxRegistry;
use PHPUnit\Event\Test\{Finished, FinishedSubscriber};

/**
 * RestoreSandboxSubscriber.
 *
 * Restores the sandboxed state after each test. PHPUnit emits Test\Finished
 * for every test regardless of its outcome (passed, failed, errored), which
 * is exactly the guarantee hand-written tearDown() methods cannot give when
 * they are skipped by earlier failures.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class RestoreSandboxSubscriber implements FinishedSubscriber
{
    public function __construct(
        private SandboxRegistry $registry,
    ) {}

    public function notify(Finished $event): void
    {
        $this->registry->restoreAll();
    }
}

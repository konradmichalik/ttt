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
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\{PreparationStarted, PreparationStartedSubscriber};

/**
 * ApplySandboxSubscriber.
 *
 * Applies all Terrarium attributes of the upcoming test right before PHPUnit
 * prepares it - i.e. before setUp() runs, so setUp() already observes the
 * sandboxed state.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ApplySandboxSubscriber implements PreparationStartedSubscriber
{
    public function __construct(
        private SandboxRegistry $registry,
    ) {}

    public function notify(PreparationStarted $event): void
    {
        $test = $event->test();

        if (!$test instanceof TestMethod) {
            return;
        }

        $this->registry->applyFor($test->className(), $test->methodName());
    }
}

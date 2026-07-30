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
use PHPUnit\Event\Test\{Prepared, PreparedSubscriber};

/**
 * ApplySandboxSubscriber.
 *
 * Applies all Terrarium attributes of the upcoming test once PHPUnit has
 * finished preparing it - i.e. after setUp() (and any #[Before]/
 * #[PreCondition] hooks) ran, and immediately before the test method body.
 * setUp() therefore never observes Terrarium-managed state; use the
 * imperative traits (e.g. ConfVarsSandbox) if setUp() needs to see it.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ApplySandboxSubscriber implements PreparedSubscriber
{
    public function __construct(
        private SandboxRegistry $registry,
    ) {}

    public function notify(Prepared $event): void
    {
        $test = $event->test();

        if (!$test instanceof TestMethod) {
            return;
        }

        $this->registry->applyFor($test->className(), $test->methodName());
    }
}

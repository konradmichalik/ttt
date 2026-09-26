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
use RuntimeException;

use function is_a;
use function sprintf;

/**
 * ApplySandboxSubscriber.
 *
 * Applies all Terrarium attributes of the upcoming test once PHPUnit has
 * finished preparing it - i.e. after setUp() (and any #[Before]/
 * #[PreCondition] hooks) ran, and immediately before the test method body.
 * setUp() therefore never observes Terrarium-managed state; use the
 * imperative traits (e.g. ConfVarsSandbox) if setUp() needs to see it.
 *
 * Under process isolation PHPUnit forwards the child's events to the parent
 * only after the test body already ran there, so attributes cannot take
 * effect. Attributed tests fail loudly instead of silently running without
 * their declared state.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final readonly class ApplySandboxSubscriber implements PreparedSubscriber
{
    // String literal, not ::class: #[RunClassInSeparateProcess] was removed
    // in PHPUnit 13, so the class may not exist to reference.
    private const RUN_CLASS_IN_SEPARATE_PROCESS = 'PHPUnit\Metadata\RunClassInSeparateProcess';

    public function __construct(
        private SandboxRegistry $registry,
        private bool $processIsolation = false,
    ) {}

    public function notify(Prepared $event): void
    {
        $test = $event->test();

        if (!$test instanceof TestMethod) {
            return;
        }

        if ($this->runsInSeparateProcess($test)) {
            if ($this->registry->hasAttributesFor($test->className(), $test->methodName())) {
                throw new RuntimeException(sprintf('Terrarium attributes on %s::%s have no effect under process isolation: PHPUnit runs the test body in a child process before Terrarium receives the Test\Prepared event. Remove the process isolation or apply the state imperatively inside the test (ConfVarsSandbox, EnvVarSandbox, ApplicationContextSwitcher).', $test->className(), $test->methodName()), 1753900401);
            }

            return;
        }

        $this->registry->applyFor($test->className(), $test->methodName());
    }

    private function runsInSeparateProcess(TestMethod $test): bool
    {
        if ($this->processIsolation) {
            return true;
        }

        foreach ($test->metadata() as $metadata) {
            if ($metadata->isRunInSeparateProcess() || $metadata->isRunTestsInSeparateProcesses() || is_a($metadata, self::RUN_CLASS_IN_SEPARATE_PROCESS)) {
                return true;
            }
        }

        return false;
    }
}

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

use KonradMichalik\Ttt\Attribute\TttAttribute;
use KonradMichalik\Ttt\Registry\SandboxRegistry;
use KonradMichalik\Ttt\Runtime\RunningTestCase;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\{Prepared, PreparedSubscriber};
use RuntimeException;

use function array_filter;
use function array_values;
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
 * Terrarium attributes inside the current data set (#[TestWith] or
 * #[DataProvider]) apply after the class- and method-level ones and
 * therefore take precedence over them.
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

        $dataSetAttributes = self::dataSetAttributes($test);

        if ($this->runsInSeparateProcess($test)) {
            if ([] !== $dataSetAttributes || $this->registry->hasAttributesFor($test->className(), $test->methodName())) {
                throw new RuntimeException(sprintf('Terrarium attributes on %s::%s have no effect under process isolation: PHPUnit runs the test body in a child process before Terrarium receives the Test\Prepared event. Remove the process isolation or apply the state imperatively inside the test (ConfVarsSandbox, EnvVarSandbox, ApplicationContextSwitcher).', $test->className(), $test->methodName()), 1753900401);
            }

            return;
        }

        $this->registry->applyFor($test->className(), $test->methodName(), $dataSetAttributes);
    }

    /**
     * PHPUnit has already materialized the data set (from #[TestWith] or
     * #[DataProvider]) on the running TestCase, so reading it back avoids
     * replicating PHPUnit's data set key assignment, which differs between
     * majors.
     *
     * @return list<TttAttribute>
     */
    private static function dataSetAttributes(TestMethod $test): array
    {
        if (!$test->testData()->hasDataFromDataProvider()) {
            return [];
        }

        $testCase = RunningTestCase::find();

        if (null === $testCase || $testCase::class !== $test->className() || $testCase->name() !== $test->methodName()) {
            throw new RuntimeException(sprintf('Terrarium cannot read the data set of %s::%s, so attributes inside it cannot be applied.', $test->className(), $test->methodName()), 1753900402);
        }

        // providedData() is @internal but unchanged from PHPUnit 10.5 to 13.
        // Should it ever disappear, the call fails loudly (PHPUnit reports
        // subscriber errors as warnings) instead of silently skipping variants.
        return array_values(array_filter($testCase->providedData(), static fn (mixed $value): bool => $value instanceof TttAttribute));
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

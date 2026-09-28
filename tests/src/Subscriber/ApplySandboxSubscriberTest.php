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

namespace KonradMichalik\Ttt\Tests\Subscriber;

use KonradMichalik\Ttt\Attribute\WithTypo3ConfVars;
use KonradMichalik\Ttt\Handler\ConfVarsHandler;
use KonradMichalik\Ttt\Registry\SandboxRegistry;
use KonradMichalik\Ttt\Subscriber\ApplySandboxSubscriber;
use PHPUnit\Event\Code\Phpt;
use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\TestData\{DataFromDataProvider, TestDataCollection};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\{Metadata, MetadataCollection};
use RuntimeException;

use function class_exists;

/**
 * ApplySandboxSubscriberTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ApplySandboxSubscriber::class)]
final class ApplySandboxSubscriberTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
    }

    #[Test]
    public function appliesAttributesOfTheUpcomingTestMethod(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler()]);
        $subscriber = new ApplySandboxSubscriber($registry);

        $subscriber->notify(new Prepared(
            TestEventFactory::telemetryInfo(),
            TestEventFactory::testMethod(SubscriberFixture::class, 'annotatedMethod'),
        ));

        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['SYS']['fromSubscriber']);

        $registry->restoreAll();
    }

    #[Test]
    public function ignoresTestsThatAreNotTestMethods(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler()]);
        $subscriber = new ApplySandboxSubscriber($registry);

        $subscriber->notify(new Prepared(
            TestEventFactory::telemetryInfo(),
            new Phpt('fixture.phpt'),
        ));

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }

    /**
     * @return iterable<string, array{Metadata}>
     */
    public static function processIsolationMetadata(): iterable
    {
        yield 'RunInSeparateProcess' => [Metadata::runInSeparateProcess()];
        yield 'RunTestsInSeparateProcesses' => [Metadata::runTestsInSeparateProcesses()];

        // Removed in PHPUnit 13.
        if (class_exists('PHPUnit\Metadata\RunClassInSeparateProcess')) {
            yield 'RunClassInSeparateProcess' => [Metadata::runClassInSeparateProcess()];
        }
    }

    #[Test]
    #[DataProvider('processIsolationMetadata')]
    public function failsForAttributedTestsRunningInASeparateProcess(Metadata $metadata): void
    {
        $subscriber = new ApplySandboxSubscriber(new SandboxRegistry([new ConfVarsHandler()]));

        try {
            $subscriber->notify(new Prepared(
                TestEventFactory::telemetryInfo(),
                TestEventFactory::testMethod(SubscriberFixture::class, 'annotatedMethod', MetadataCollection::fromArray([$metadata])),
            ));
            self::fail('Expected a RuntimeException.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString(SubscriberFixture::class.'::annotatedMethod', $exception->getMessage());
            self::assertStringContainsString('process isolation', $exception->getMessage());
        }

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }

    #[Test]
    public function failsForAttributedTestsWhenProcessIsolationIsConfigured(): void
    {
        $subscriber = new ApplySandboxSubscriber(new SandboxRegistry([new ConfVarsHandler()]), true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1753900401);

        $subscriber->notify(new Prepared(
            TestEventFactory::telemetryInfo(),
            TestEventFactory::testMethod(SubscriberFixture::class, 'annotatedMethod'),
        ));
    }

    #[Test]
    public function failsWhenTheDataSetOfTheTestCannotBeRead(): void
    {
        $subscriber = new ApplySandboxSubscriber(new SandboxRegistry([new ConfVarsHandler()]));

        // The running TestCase on the call stack is this test, not the one
        // the event describes, so its data set must not be used.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1753900402);

        $subscriber->notify(new Prepared(
            TestEventFactory::telemetryInfo(),
            TestEventFactory::testMethod(
                SubscriberFixture::class,
                'annotatedMethod',
                testData: TestDataCollection::fromArray([DataFromDataProvider::from('variant', '', '')]),
            ),
        ));
    }

    #[Test]
    public function ignoresTestsWithoutAttributesRunningInASeparateProcess(): void
    {
        $subscriber = new ApplySandboxSubscriber(new SandboxRegistry([new ConfVarsHandler()]), true);

        $subscriber->notify(new Prepared(
            TestEventFactory::telemetryInfo(),
            TestEventFactory::testMethod(SubscriberFixture::class, 'plainMethod', MetadataCollection::fromArray([Metadata::runInSeparateProcess()])),
        ));

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }
}

/**
 * SubscriberFixture.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class SubscriberFixture
{
    #[WithTypo3ConfVars(['SYS' => ['fromSubscriber' => true]])]
    public function annotatedMethod(): void {}

    public function plainMethod(): void {}
}

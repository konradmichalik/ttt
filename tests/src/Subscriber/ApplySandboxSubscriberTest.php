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
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

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
}

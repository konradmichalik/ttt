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

use KonradMichalik\Ttt\Handler\ConfVarsHandler;
use KonradMichalik\Ttt\Registry\SandboxRegistry;
use KonradMichalik\Ttt\Subscriber\RestoreSandboxSubscriber;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

/**
 * RestoreSandboxSubscriberTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(RestoreSandboxSubscriber::class)]
final class RestoreSandboxSubscriberTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
    }

    #[Test]
    public function restoresTheSandboxWhenAnyTestFinishes(): void
    {
        $registry = new SandboxRegistry([new ConfVarsHandler()]);
        $registry->applyFor(SubscriberFixture::class, 'annotatedMethod');

        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['SYS']['fromSubscriber']);

        (new RestoreSandboxSubscriber($registry))->notify(new Finished(
            TestEventFactory::telemetryInfo(),
            TestEventFactory::testMethod(SubscriberFixture::class, 'annotatedMethod'),
            0,
        ));

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }
}

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

use PHPUnit\Event\Code\{TestDox, TestMethod};
use PHPUnit\Event\Telemetry\{Duration, GarbageCollectorStatus, HRTime, Info, MemoryUsage, Snapshot};
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Metadata\MetadataCollection;

/**
 * TestEventFactory.
 *
 * Builds the minimal PHPUnit value objects required to construct real
 * lifecycle events (Telemetry\Info, TestMethod) for subscriber unit tests -
 * the events are final readonly and therefore cannot be mocked.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class TestEventFactory
{
    private function __construct() {}

    public static function telemetryInfo(): Info
    {
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);

        $snapshot = new Snapshot(
            HRTime::fromSecondsAndNanoseconds(0, 0),
            $memory,
            $memory,
            new GarbageCollectorStatus(0, 0, 0, 0, null, null, null, null, null, null, null, null),
        );

        return new Info($snapshot, $duration, $memory, $duration, $memory);
    }

    /**
     * @param class-string     $className
     * @param non-empty-string $methodName
     */
    public static function testMethod(string $className, string $methodName): TestMethod
    {
        return new TestMethod(
            $className,
            $methodName,
            'FakeTest.php',
            1,
            new TestDox('Fake', $methodName, $methodName),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
    }
}

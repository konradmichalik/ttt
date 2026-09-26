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

namespace KonradMichalik\Ttt\Tests\Integration;

use KonradMichalik\Ttt\Subscriber\ApplySandboxSubscriber;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function implode;
use function sprintf;

/**
 * ProcessIsolationTest.
 *
 * Runs a separate PHPUnit process against tests/fixtures/ProcessIsolation:
 * only a real run shows how PHPUnit forwards the child's events to the
 * parent, which is where the guard has to fire.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ApplySandboxSubscriber::class)]
final class ProcessIsolationTest extends TestCase
{
    #[Test]
    public function attributedTestsFailLoudlyWhileTestsWithoutAttributesAreUnaffected(): void
    {
        $root = dirname(__DIR__, 3);
        $fixtureDirectory = $root.'/tests/fixtures';

        exec(sprintf(
            '%s %s --no-coverage -c %s %s 2>&1',
            escapeshellarg(\PHP_BINARY),
            escapeshellarg($root.'/vendor/bin/phpunit'),
            escapeshellarg($fixtureDirectory.'/phpunit.xml'),
            escapeshellarg($fixtureDirectory.'/ProcessIsolation/ProcessIsolationFixtureTest.php'),
        ), $output);

        $output = implode("\n", $output);

        self::assertStringContainsString('ProcessIsolationFixtureTest::attributedTestInSeparateProcess have no effect under process isolation', $output);
        self::assertStringContainsString('ProcessIsolationFixtureTest::dataSetAttributeInSeparateProcess have no effect under process isolation', $output);
        self::assertStringNotContainsString('plainTestInSeparateProcess', $output);
        self::assertStringContainsString('Tests: 3', $output);
    }
}

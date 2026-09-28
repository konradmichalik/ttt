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
 * FailingVariantRestorationTest.
 *
 * Runs a separate PHPUnit process against tests/fixtures/DataSet, whose
 * first variant fails on purpose: the state it declared must be restored
 * before the next variant runs.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ApplySandboxSubscriber::class)]
final class FailingVariantRestorationTest extends TestCase
{
    #[Test]
    public function stateOfAFailingVariantIsRestoredBeforeTheNextVariantRuns(): void
    {
        $fixtureDirectory = dirname(__DIR__, 3).'/tests/fixtures';

        exec(sprintf(
            '%s %s --no-coverage -c %s %s 2>&1',
            escapeshellarg(\PHP_BINARY),
            escapeshellarg(dirname(__DIR__, 3).'/vendor/bin/phpunit'),
            escapeshellarg($fixtureDirectory.'/phpunit.xml'),
            escapeshellarg($fixtureDirectory.'/DataSet/FailingVariantFixtureTest.php'),
        ), $output);

        $output = implode("\n", $output);

        self::assertStringContainsString('The first variant fails on purpose.', $output);
        self::assertStringNotContainsString('The failing variant leaked into the next one.', $output);
        self::assertStringContainsString('Tests: 2', $output);
        self::assertStringContainsString('Failures: 1', $output);
    }
}

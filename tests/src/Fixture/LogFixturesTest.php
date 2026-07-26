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

namespace KonradMichalik\Ttt\Tests\Fixture;

use KonradMichalik\Ttt\Fixture\LogFixtures;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function bin2hex;
use function file_get_contents;
use function file_put_contents;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

/**
 * LogFixturesTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(LogFixtures::class)]
final class LogFixturesTest extends TestCase
{
    #[Test]
    public function writesLinesSeparatedByEolIncludingTrailingNewline(): void
    {
        $directory = sys_get_temp_dir().'/ttt-log-'.bin2hex(random_bytes(8));
        $path = $directory.'/app.log';

        LogFixtures::write($path, ['line one', 'line two']);

        self::assertSame('line one'.\PHP_EOL.'line two'.\PHP_EOL, file_get_contents($path));

        unlink($path);
        rmdir($directory);
    }

    #[Test]
    public function failsWhenTheTargetDirectoryCannotBeCreated(): void
    {
        // Use a regular file as the parent path so mkdir() below it fails.
        $file = sys_get_temp_dir().'/ttt-log-file-'.bin2hex(random_bytes(8));
        file_put_contents($file, 'not a directory');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionCode(1752561606);

            LogFixtures::write($file.'/nested/app.log', ['x']);
        } finally {
            unlink($file);
        }
    }
}

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

namespace KonradMichalik\Ttt\Fixture;

use RuntimeException;

use function dirname;
use function file_put_contents;
use function implode;
use function is_dir;
use function mkdir;
use function sprintf;

/**
 * LogFixtures.
 *
 * Writes line-based log fixtures to disk - shared replacement for
 * per-repository "writeLog" test helpers.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class LogFixtures
{
    // Static-only utility: the constructor exists solely to forbid instantiation.
    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    /**
     * @param list<string> $lines
     */
    public static function write(string $path, array $lines): void
    {
        if (!is_dir(dirname($path)) && !@mkdir(dirname($path), 0o700, true) && !is_dir(dirname($path))) {
            throw new RuntimeException(sprintf('Unable to create directory "%s".', dirname($path)), 1752561606);
        }

        file_put_contents($path, implode(\PHP_EOL, $lines).\PHP_EOL);
    }
}

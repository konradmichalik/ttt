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

namespace KonradMichalik\Ttt\Tests\Fixtures\PHPStan;

use PHPUnit\Framework\TestCase;


/**
 * EnvTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

final class EnvTest extends TestCase
{
    public function setsAndClears(): void
    {
        putenv('MY_EXT_FEATURE=1');
        putenv('MY_EXT_FEATURE');
        putenv('MY_EXT_OTHER=1');
    }

    public function otherFunctionsAreIgnored(): void
    {
        getenv('MY_EXT_FEATURE');
    }
}


/**
 * NotAnEnvTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */

final class NotAnEnvTest
{
    public function run(): void
    {
        putenv('X=1');
    }
}

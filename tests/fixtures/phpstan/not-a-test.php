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

namespace Fixture\PHPStan;

/**
 * NotATest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class NotATest
{
    public function change(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = [];
        putenv('FOO=bar');
    }
}

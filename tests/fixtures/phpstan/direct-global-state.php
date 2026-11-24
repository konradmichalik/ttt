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

use PHPUnit\Framework\TestCase;
use stdClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DirectGlobalStateTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class DirectGlobalStateTest extends TestCase
{
    public function confVars(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'direct';
    }

    public function backendUser(): void
    {
        $GLOBALS['BE_USER'] = new stdClass();
    }

    public function tca(): void
    {
        $GLOBALS['TCA']['pages']['columns'] = [];
    }

    public function otherGlobal(): void
    {
        $GLOBALS['LANG'] = new stdClass();
    }

    public function dynamicKey(string $key): void
    {
        $GLOBALS[$key] = 1;
    }

    public function environment(): void
    {
        putenv('FOO=bar');
    }

    public function singleton(): void
    {
        GeneralUtility::setSingletonInstance(stdClass::class, new stdClass());
    }

    public function instance(): void
    {
        GeneralUtility::addInstance(stdClass::class, new stdClass());
    }

    public function allowed(): void
    {
        $local = ['TYPO3_CONF_VARS' => []];
        $local['TYPO3_CONF_VARS']['SYS'] = 1;
        $value = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        GeneralUtility::makeInstance(stdClass::class);
        $callable = putenv(...);
        $staticCallable = GeneralUtility::addInstance(...);
        $text = <<<'TXT'
            $GLOBALS['TYPO3_CONF_VARS'] = [];
            TXT;
    }
}

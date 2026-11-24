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

namespace KonradMichalik\Ttt\Handler;

use Closure;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;

/**
 * SingletonCleanup.
 *
 * Shared by handlers that obtain a singleton through
 * GeneralUtility::makeInstance(): if the singleton did not exist before the
 * handler ran, it must not outlive the test. A singleton that existed
 * already is left alone.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class SingletonCleanup
{
    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    /**
     * Call BEFORE the first makeInstance() of the class.
     *
     * @param class-string $className
     *
     * @return Closure(): void Drops the singleton again if it was created after this call
     */
    public static function track(string $className): Closure
    {
        $existed = array_key_exists($className, GeneralUtility::getSingletonInstances());

        return static function () use ($className, $existed): void {
            if ($existed) {
                return;
            }

            $singletons = GeneralUtility::getSingletonInstances();
            unset($singletons[$className]);
            GeneralUtility::resetSingletonInstances($singletons);
        };
    }
}

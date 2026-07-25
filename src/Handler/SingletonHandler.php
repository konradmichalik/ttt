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
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithSingleton};
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function assert;
use function is_string;

/**
 * SingletonHandler.
 *
 * Applies WithSingleton: snapshots the current singleton map, registers the
 * given instance via GeneralUtility::setSingletonInstance() and restores the
 * exact previous map afterwards.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class SingletonHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithSingleton;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithSingleton);

        $snapshot = GeneralUtility::getSingletonInstances();

        $instance = is_string($attribute->instance) ? new ($attribute->instance)() : $attribute->instance;
        assert($instance instanceof SingletonInterface);

        GeneralUtility::setSingletonInstance($attribute->className, $instance);

        return static fn () => GeneralUtility::resetSingletonInstances($snapshot);
    }
}

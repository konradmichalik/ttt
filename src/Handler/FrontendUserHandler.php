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
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithFrontendUser};
use TYPO3\CMS\Core\Context\{Context, UserAspect};
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

use function array_key_exists;
use function assert;

/**
 * FrontendUserHandler.
 *
 * Applies WithFrontendUser: places a lightweight FrontendUserAuthentication
 * stub (constructor skipped, user record populated) into $GLOBALS['FE_USER'],
 * registers it as the Context's frontend.user aspect, and restores both
 * afterwards.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class FrontendUserHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithFrontendUser;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithFrontendUser);

        $existedGlobal = array_key_exists('FE_USER', $GLOBALS);
        $previousGlobal = $GLOBALS['FE_USER'] ?? null;

        $user = new class extends FrontendUserAuthentication {
            public function __construct() {}
        };
        $user->user = [
            'uid' => $attribute->uid,
            'username' => $attribute->username,
        ];

        $GLOBALS['FE_USER'] = $user;

        $context = GeneralUtility::makeInstance(Context::class);
        $restoreAspect = ContextAspectSandbox::apply($context, 'frontend.user', new UserAspect($user, $attribute->groups));

        return static function () use ($existedGlobal, $previousGlobal, $restoreAspect): void {
            if ($existedGlobal) {
                $GLOBALS['FE_USER'] = $previousGlobal;
            } else {
                unset($GLOBALS['FE_USER']);
            }

            $restoreAspect();
        };
    }
}

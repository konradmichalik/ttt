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
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithBackendUser};
use ReflectionProperty;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\{AspectInterface, Context, UserAspect};
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function assert;

/**
 * BackendUserHandler.
 *
 * Applies WithBackendUser: places a lightweight BackendUserAuthentication
 * stub (constructor skipped, user record populated) into $GLOBALS['BE_USER'],
 * registers it as the Context's backend.user aspect, and restores both
 * afterwards.
 *
 * Context::hasAspect() returns true unconditionally for backend.user (one of
 * six default aspects), so prior presence is snapshotted via reflection on
 * the protected Context::$aspects array instead.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class BackendUserHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithBackendUser;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithBackendUser);

        $existedGlobal = array_key_exists('BE_USER', $GLOBALS);
        $previousGlobal = $GLOBALS['BE_USER'] ?? null;

        $user = new class extends BackendUserAuthentication {
            public function __construct() {}
        };
        $user->user = [
            'uid' => $attribute->uid,
            'username' => $attribute->username,
            'admin' => $attribute->admin ? 1 : 0,
        ];
        $user->workspace = $attribute->workspace;
        $user->userGroupsUID = $attribute->groups;

        $GLOBALS['BE_USER'] = $user;

        $context = GeneralUtility::makeInstance(Context::class);
        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        /** @var array<string, AspectInterface> $aspectsSnapshot */
        $aspectsSnapshot = $aspectsProperty->getValue($context);
        $existedAspect = array_key_exists('backend.user', $aspectsSnapshot);
        $previousAspect = $aspectsSnapshot['backend.user'] ?? null;

        $context->setAspect('backend.user', new UserAspect($user, $attribute->groups));

        return static function () use ($existedGlobal, $previousGlobal, $context, $existedAspect, $previousAspect): void {
            if ($existedGlobal) {
                $GLOBALS['BE_USER'] = $previousGlobal;
            } else {
                unset($GLOBALS['BE_USER']);
            }

            if ($existedAspect && null !== $previousAspect) {
                $context->setAspect('backend.user', $previousAspect);
            } else {
                $context->unsetAspect('backend.user');
            }
        };
    }
}

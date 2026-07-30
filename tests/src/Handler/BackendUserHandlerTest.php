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

namespace KonradMichalik\Ttt\Tests\Handler;

use KonradMichalik\Ttt\Attribute\WithBackendUser;
use KonradMichalik\Ttt\Handler\{BackendUserHandler, ContextAspectSandbox};
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\{Context, UserAspect};
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * BackendUserHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(BackendUserHandler::class)]
#[CoversClass(WithBackendUser::class)]
#[CoversClass(ContextAspectSandbox::class)]
final class BackendUserHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
    }

    #[Test]
    public function providesAdminStubAndRestoresUnsetGlobal(): void
    {
        $restore = (new BackendUserHandler())->apply(new WithBackendUser(admin: true, uid: 42));

        $backendUser = $GLOBALS['BE_USER'];
        self::assertInstanceOf(BackendUserAuthentication::class, $backendUser);
        self::assertTrue($backendUser->isAdmin());

        $user = $backendUser->user;
        self::assertIsArray($user);
        self::assertSame(42, $user['uid']);

        $restore();

        self::assertArrayNotHasKey('BE_USER', $GLOBALS);
    }

    #[Test]
    public function setsTheBackendUserContextAspect(): void
    {
        $restore = (new BackendUserHandler())->apply(new WithBackendUser(admin: true, uid: 42, groups: [3, 7]));

        $context = GeneralUtility::makeInstance(Context::class);
        self::assertTrue($context->getPropertyFromAspect('backend.user', 'isAdmin'));
        self::assertSame(42, $context->getPropertyFromAspect('backend.user', 'id'));
        self::assertSame([3, 7], $context->getPropertyFromAspect('backend.user', 'groupIds'));

        $restore();
    }

    #[Test]
    public function restoresAnAbsentBackendUserAspectToAbsent(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        self::assertArrayNotHasKey('backend.user', $aspectsProperty->getValue($context));

        $restore = (new BackendUserHandler())->apply(new WithBackendUser());
        $restore();

        self::assertArrayNotHasKey('backend.user', $aspectsProperty->getValue($context));
    }

    #[Test]
    public function restoresAPreviouslySetBackendUserAspect(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $previousAspect = new UserAspect();
        $context->setAspect('backend.user', $previousAspect);

        $restore = (new BackendUserHandler())->apply(new WithBackendUser(admin: true));
        $restore();

        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        self::assertSame($previousAspect, $aspectsProperty->getValue($context)['backend.user']);

        $context->unsetAspect('backend.user');
    }

    #[Test]
    public function workspaceDefaultsToZero(): void
    {
        $restore = (new BackendUserHandler())->apply(new WithBackendUser());

        self::assertSame(0, $GLOBALS['BE_USER']->workspace);

        $restore();
    }

    #[Test]
    public function workspaceIsConfigurable(): void
    {
        $restore = (new BackendUserHandler())->apply(new WithBackendUser(workspace: 5));

        self::assertSame(5, $GLOBALS['BE_USER']->workspace);

        $restore();
    }

    #[Test]
    public function populatesUserGroupsUidForGroupMembershipChecks(): void
    {
        $restore = (new BackendUserHandler())->apply(new WithBackendUser(groups: [3, 7]));

        self::assertSame([3, 7], $GLOBALS['BE_USER']->userGroupsUID);

        $restore();
    }

    #[Test]
    public function defaultsToNoGroupMemberships(): void
    {
        $restore = (new BackendUserHandler())->apply(new WithBackendUser());

        self::assertSame([], $GLOBALS['BE_USER']->userGroupsUID);

        $restore();
    }

    #[Test]
    public function restoresPreviousGlobal(): void
    {
        $previous = new stdClass();
        $GLOBALS['BE_USER'] = $previous;

        $restore = (new BackendUserHandler())->apply(new WithBackendUser());
        $restore();

        self::assertSame($previous, $GLOBALS['BE_USER']);
    }
}

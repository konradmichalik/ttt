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
use KonradMichalik\Ttt\Handler\BackendUserHandler;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use stdClass;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * BackendUserHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(BackendUserHandler::class)]
#[CoversClass(WithBackendUser::class)]
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

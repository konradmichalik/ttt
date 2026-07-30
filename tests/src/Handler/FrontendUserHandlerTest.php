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

use KonradMichalik\Ttt\Attribute\{WithBackendUser, WithFrontendUser};
use KonradMichalik\Ttt\Handler\{BackendUserHandler, FrontendUserHandler};
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use TYPO3\CMS\Core\Context\{Context, UserAspect};
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * FrontendUserHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(FrontendUserHandler::class)]
#[CoversClass(WithFrontendUser::class)]
final class FrontendUserHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['FE_USER']);
    }

    #[Test]
    public function providesAnAnonymousStubByDefaultAndRestoresUnsetGlobal(): void
    {
        $restore = (new FrontendUserHandler())->apply(new WithFrontendUser());

        $frontendUser = $GLOBALS['FE_USER'];
        self::assertInstanceOf(FrontendUserAuthentication::class, $frontendUser);

        $context = GeneralUtility::makeInstance(Context::class);
        self::assertFalse($context->getPropertyFromAspect('frontend.user', 'isLoggedIn'));

        $restore();

        self::assertArrayNotHasKey('FE_USER', $GLOBALS);
    }

    #[Test]
    public function providesALoggedInUserStub(): void
    {
        $restore = (new FrontendUserHandler())->apply(new WithFrontendUser(uid: 42, groups: [3, 7]));

        $user = $GLOBALS['FE_USER']->user;
        self::assertIsArray($user);
        self::assertSame(42, $user['uid']);

        $context = GeneralUtility::makeInstance(Context::class);
        self::assertTrue($context->getPropertyFromAspect('frontend.user', 'isLoggedIn'));
        self::assertSame(42, $context->getPropertyFromAspect('frontend.user', 'id'));
        self::assertSame([3, 7], $context->getPropertyFromAspect('frontend.user', 'groupIds'));

        $restore();
    }

    #[Test]
    public function restoresAnAbsentFrontendUserAspectToAbsent(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        self::assertArrayNotHasKey('frontend.user', $aspectsProperty->getValue($context));

        $restore = (new FrontendUserHandler())->apply(new WithFrontendUser());
        $restore();

        self::assertArrayNotHasKey('frontend.user', $aspectsProperty->getValue($context));
    }

    #[Test]
    public function restoresAPreviouslySetFrontendUserAspect(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $previousAspect = new UserAspect();
        $context->setAspect('frontend.user', $previousAspect);

        $restore = (new FrontendUserHandler())->apply(new WithFrontendUser(uid: 42));
        $restore();

        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        self::assertSame($previousAspect, $aspectsProperty->getValue($context)['frontend.user']);

        $context->unsetAspect('frontend.user');
    }

    #[Test]
    public function restoresPreviousGlobal(): void
    {
        $previous = new stdClass();
        $GLOBALS['FE_USER'] = $previous;

        $restore = (new FrontendUserHandler())->apply(new WithFrontendUser());
        $restore();

        self::assertSame($previous, $GLOBALS['FE_USER']);
    }

    #[Test]
    public function combinesWithABackendUserOnTheSameTest(): void
    {
        $restoreBackend = (new BackendUserHandler())->apply(new WithBackendUser(admin: true, uid: 1));
        $restoreFrontend = (new FrontendUserHandler())->apply(new WithFrontendUser(uid: 2));

        $context = GeneralUtility::makeInstance(Context::class);
        self::assertTrue($context->getPropertyFromAspect('backend.user', 'isAdmin'));
        self::assertSame(1, $context->getPropertyFromAspect('backend.user', 'id'));
        self::assertTrue($context->getPropertyFromAspect('frontend.user', 'isLoggedIn'));
        self::assertSame(2, $context->getPropertyFromAspect('frontend.user', 'id'));

        $restoreFrontend();
        $restoreBackend();

        $aspectsProperty = new ReflectionProperty(Context::class, 'aspects');
        self::assertArrayNotHasKey('backend.user', $aspectsProperty->getValue($context));
        self::assertArrayNotHasKey('frontend.user', $aspectsProperty->getValue($context));
    }
}

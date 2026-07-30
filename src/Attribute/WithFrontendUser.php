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

namespace KonradMichalik\Ttt\Attribute;

use Attribute;

/**
 * WithFrontendUser.
 *
 * Counterpart to WithBackendUser: provides a lightweight $GLOBALS['FE_USER']
 * stub (a FrontendUserAuthentication subclass with a skipped constructor and
 * a populated user record), registers it as the Context's frontend.user
 * aspect, and restores both afterwards.
 *
 * $GLOBALS['FE_USER'] is carried alongside the aspect as a compatibility
 * fallback: it remains a common touchpoint in TYPO3 code (e.g. via
 * $GLOBALS['TSFE']->fe_user or direct global access) even where the modern
 * Context aspect is also read.
 *
 * Defaults to an anonymous, not-logged-in user (uid: 0) - use #[WithFrontendUser]
 * on its own to simulate the "not logged in" case explicitly. The optional
 * $groups parameter populates the aspect's groupIds directly (bypassing the
 * default anonymous/logged-in group calculation), so isMemberOfGroup()-style
 * checks work for tests that check frontend-user-group membership.
 *
 * Requires typo3/cms-frontend.
 *
 * <code>
 * #[WithFrontendUser(uid: 42, groups: [1, 2])]
 * #[WithFrontendUser]   // anonymous, explicitly not logged in
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class WithFrontendUser implements TttAttribute
{
    /**
     * @param list<int> $groups
     */
    public function __construct(
        public int $uid = 0,
        public string $username = '',
        public array $groups = [],
    ) {}
}

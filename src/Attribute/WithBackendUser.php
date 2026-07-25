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
 * WithBackendUser.
 *
 * Provides a lightweight $GLOBALS['BE_USER'] stub (a
 * BackendUserAuthentication subclass with a skipped constructor and a
 * populated user record) and restores the previous global afterwards.
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class WithBackendUser implements TttAttribute
{
    public function __construct(
        public bool $admin = false,
        public int $uid = 1,
        public string $username = 'ttt',
    ) {}
}

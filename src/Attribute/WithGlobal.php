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
 * WithGlobal.
 *
 * Sets an arbitrary $GLOBALS entry before the test body runs (after setUp()) and restores
 * the previous value (including a previously unset key) afterwards. For
 * $GLOBALS['TYPO3_CONF_VARS'] specifically, prefer WithTypo3ConfVars, which
 * deep-merges instead of overwriting.
 *
 * <code>
 * #[WithGlobal('MY_APP_SETTINGS', ['mode' => 'strict'])]
 * public function behavesInStrictMode(): void {}
 * </code>
 *
 * Attribute arguments must be constant expressions. For a value that cannot be
 * written as one (a prepared request, a mock), assign it inside the test and
 * use the imperative sandboxes instead.
 *
 * <code>
 * #[WithGlobal('TYPO3_REQUEST', new ServerRequest('https://example.com/'))]
 * public function resolvesFromCurrentRequest(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithGlobal implements TttAttribute
{
    public function __construct(
        public string $key,
        public mixed $value,
    ) {}
}

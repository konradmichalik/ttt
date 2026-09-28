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
 * WithSuperglobal.
 *
 * Sets a single key of a PHP superglobal (one of $_SERVER, $_GET, $_POST,
 * $_ENV) before the test is prepared and restores the previous value
 * (including a previously unset key) afterwards. For actual
 * environment variables, prefer WithEnvVar, which also drives putenv().
 *
 * Passing null as the value unsets the key instead of setting it.
 *
 * <code>
 * #[WithSuperglobal('_SERVER', 'REMOTE_ADDR', '203.0.113.1')]
 * public function resolvesClientIp(): void {}
 * </code>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class WithSuperglobal implements TttAttribute
{
    public function __construct(
        public string $superglobal,
        public string $key,
        public ?string $value = null,
    ) {}
}

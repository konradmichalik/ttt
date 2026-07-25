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
use InvalidArgumentException;
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithEnvVar};

use function array_key_exists;
use function assert;
use function getenv;
use function preg_match;
use function putenv;
use function sprintf;

/**
 * EnvVarHandler.
 *
 * Sets an environment variable via putenv(), $_ENV and $_SERVER and restores
 * the previous state of all three channels via the returned restorer.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class EnvVarHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithEnvVar;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof WithEnvVar);

        $name = $attribute->name;

        if (1 !== preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException(sprintf('Invalid environment variable name "%s".', $name), 1752561605);
        }
        $previous = getenv($name);
        $previousEnv = array_key_exists($name, $_ENV) ? $_ENV[$name] : null;
        $envExisted = array_key_exists($name, $_ENV);
        $previousServer = array_key_exists($name, $_SERVER) ? $_SERVER[$name] : null;
        $serverExisted = array_key_exists($name, $_SERVER);

        putenv(sprintf('%s=%s', $name, $attribute->value));
        $_ENV[$name] = $attribute->value;
        $_SERVER[$name] = $attribute->value;

        return static function () use ($name, $previous, $previousEnv, $envExisted, $previousServer, $serverExisted): void {
            if (false === $previous) {
                putenv($name);
            } else {
                putenv(sprintf('%s=%s', $name, $previous));
            }

            if ($envExisted) {
                $_ENV[$name] = $previousEnv;
            } else {
                unset($_ENV[$name]);
            }

            if ($serverExisted) {
                $_SERVER[$name] = $previousServer;
            } else {
                unset($_SERVER[$name]);
            }
        };
    }
}

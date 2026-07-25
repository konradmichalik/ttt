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
use Error;
use KonradMichalik\Ttt\Attribute\{InApplicationContext, TttAttribute};
use RuntimeException;
use TYPO3\CMS\Core\Core\{ApplicationContext, Environment};

use function assert;

/**
 * ApplicationContextHandler.
 *
 * Applies InApplicationContext by re-initializing the Environment with an
 * identical state except for the application context.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class ApplicationContextHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof InApplicationContext;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        assert($attribute instanceof InApplicationContext);

        try {
            $previousContext = (string) Environment::getContext();
        } catch (Error) {
            throw new RuntimeException('InApplicationContext requires an initialized TYPO3 Environment. Combine it with #[WithEnvironment].', 1752561601);
        }

        self::reinitializeWithContext($attribute->context);

        return static fn () => self::reinitializeWithContext($previousContext);
    }

    private static function reinitializeWithContext(string $context): void
    {
        Environment::initialize(
            new ApplicationContext($context),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX',
        );
    }
}

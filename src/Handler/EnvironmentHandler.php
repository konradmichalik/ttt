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
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithEnvironment};
use RuntimeException;
use TYPO3\CMS\Core\Core\{ApplicationContext, Environment};

use function bin2hex;
use function is_dir;
use function is_link;
use function mkdir;
use function random_bytes;
use function rmdir;
use function scandir;
use function sprintf;
use function sys_get_temp_dir;
use function unlink;

/**
 * EnvironmentHandler.
 *
 * Applies WithEnvironment: snapshots the current Environment (if initialized),
 * bootstraps a fresh one - optionally inside a self-created temporary project
 * directory - and restores/cleans up afterwards.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class EnvironmentHandler implements AttributeHandler
{
    public function supports(TttAttribute $attribute): bool
    {
        return $attribute instanceof WithEnvironment;
    }

    public function apply(TttAttribute $attribute): Closure
    {
        \assert($attribute instanceof WithEnvironment);

        $snapshot = self::snapshot();

        $createdPath = null;
        $projectPath = $attribute->projectPath;

        if (null === $projectPath && $attribute->temporaryProjectPath) {
            $projectPath = sys_get_temp_dir().'/ttt-'.bin2hex(random_bytes(16));
            $createdPath = $projectPath;
        }

        if (null === $projectPath) {
            $projectPath = sys_get_temp_dir();
        }

        if (null !== $createdPath && (is_dir($createdPath) || is_link($createdPath))) {
            // Fail closed instead of adopting a path we did not create (symlink/pre-creation race).
            throw new RuntimeException(sprintf('Temporary project path "%s" unexpectedly exists.', $createdPath), 1752561603);
        }

        foreach (['', '/public', '/var', '/config'] as $directory) {
            if (!is_dir($projectPath.$directory) && !@mkdir($projectPath.$directory, 0o700, true) && !is_dir($projectPath.$directory)) {
                throw new RuntimeException(sprintf('Unable to create directory "%s".', $projectPath.$directory), 1752561604);
            }
        }

        Environment::initialize(
            new ApplicationContext($attribute->context),
            $attribute->cli,
            $attribute->composerMode,
            $projectPath,
            $projectPath.'/public',
            $projectPath.'/var',
            $projectPath.'/config',
            $projectPath.'/public/index.php',
            'Windows' === \PHP_OS_FAMILY ? 'WINDOWS' : 'UNIX',
        );

        return static function () use ($snapshot, $createdPath): void {
            if (null !== $createdPath) {
                self::removeDirectory($createdPath);
            }

            self::restore($snapshot);
        };
    }

    /**
     * @return array{context: string, cli: bool, composerMode: bool, projectPath: string, publicPath: string, varPath: string, configPath: string, currentScript: string, os: string}|null
     */
    private static function snapshot(): ?array
    {
        try {
            return [
                'context' => (string) Environment::getContext(),
                'cli' => Environment::isCli(),
                'composerMode' => Environment::isComposerMode(),
                'projectPath' => Environment::getProjectPath(),
                'publicPath' => Environment::getPublicPath(),
                'varPath' => Environment::getVarPath(),
                'configPath' => Environment::getConfigPath(),
                'currentScript' => Environment::getCurrentScript(),
                'os' => Environment::isWindows() ? 'WINDOWS' : 'UNIX',
            ];
        } catch (\Error) {
            // Environment was never initialized in this process.
            return null;
        }
    }

    /**
     * @param array{context: string, cli: bool, composerMode: bool, projectPath: string, publicPath: string, varPath: string, configPath: string, currentScript: string, os: string}|null $snapshot
     */
    private static function restore(?array $snapshot): void
    {
        if (null === $snapshot) {
            return;
        }

        Environment::initialize(
            new ApplicationContext($snapshot['context']),
            $snapshot['cli'],
            $snapshot['composerMode'],
            $snapshot['projectPath'],
            $snapshot['publicPath'],
            $snapshot['varPath'],
            $snapshot['configPath'],
            $snapshot['currentScript'],
            $snapshot['os'],
        );
    }

    private static function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $absolute = sprintf('%s/%s', $path, $entry);

            // Never follow symlinks during cleanup - unlink the link itself instead
            // of recursing into (and deleting) its target outside the sandbox.
            if (!is_link($absolute) && is_dir($absolute)) {
                self::removeDirectory($absolute);
            } else {
                unlink($absolute);
            }
        }

        rmdir($path);
    }
}

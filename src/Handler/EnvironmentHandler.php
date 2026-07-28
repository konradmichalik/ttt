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
use KonradMichalik\Ttt\Attribute\{TttAttribute, WithEnvironment};
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use TYPO3\CMS\Core\Core\{ApplicationContext, Environment};

use function assert;
use function bin2hex;
use function debug_backtrace;
use function dirname;
use function is_dir;
use function is_file;
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
        assert($attribute instanceof WithEnvironment);

        $snapshot = self::snapshot();

        $createdPath = null;
        $projectPath = $attribute->projectPath;

        if ('self' === $projectPath) {
            $projectPath = self::resolveConsumingPackageRoot();
        }

        if (null === $projectPath && $attribute->temporaryProjectPath) {
            $projectPath = sys_get_temp_dir().'/ttt-'.bin2hex(random_bytes(16));
            $createdPath = $projectPath;
        }

        if (null === $projectPath) {
            $projectPath = sys_get_temp_dir();
        }

        if (null !== $createdPath && (is_dir($createdPath) || is_link($createdPath))) {
            // Fail closed instead of adopting a path we did not create (symlink/pre-creation race).
            // Defensive: the freshly generated random path cannot already exist.
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('Temporary project path "%s" unexpectedly exists.', $createdPath), 1752561603);
            // @codeCoverageIgnoreEnd
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
        } catch (Error) {
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

    /**
     * Walks the call stack for the PHPUnit test case currently executing and
     * resolves the directory of its own package - the nearest ancestor
     * directory of the test's declaring file that contains a composer.json.
     */
    private static function resolveConsumingPackageRoot(): string
    {
        foreach (debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT | \DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $object = $frame['object'] ?? null;

            if ($object instanceof TestCase) {
                $file = (new ReflectionClass($object::class))->getFileName();

                if (false !== $file) {
                    return self::findPackageRoot($file);
                }
            }
        }

        // Defensive: apply() only reaches the 'self' branch while ttt's own
        // PHPUnit extension is applying attributes for a running test, which
        // always has a TestCase instance somewhere in the call stack.
        // @codeCoverageIgnoreStart
        throw new RuntimeException('Unable to resolve projectPath "self": no PHPUnit\Framework\TestCase found in the call stack.', 1752561612);
        // @codeCoverageIgnoreEnd
    }

    private static function findPackageRoot(string $file): string
    {
        $directory = dirname($file);

        while (!is_file($directory.'/composer.json')) {
            $parent = dirname($directory);

            // Defensive: every test file that ttt processes lives inside a
            // Composer package, so the filesystem root is never reached in practice.
            // @codeCoverageIgnoreStart
            if ($parent === $directory) {
                throw new RuntimeException(sprintf('Unable to resolve projectPath "self": no composer.json found above "%s".', $file), 1752561613);
            }
            // @codeCoverageIgnoreEnd

            $directory = $parent;
        }

        return $directory;
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

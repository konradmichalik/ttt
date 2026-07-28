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

use KonradMichalik\Ttt\Attribute\{InApplicationContext, WithEnvironment};
use KonradMichalik\Ttt\Handler\{ApplicationContextHandler, EnvironmentHandler};
use PHPUnit\Framework\Attributes\{CoversClass, PreserveGlobalState, RunInSeparateProcess, Test};
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TYPO3\CMS\Core\Core\Environment;

use function dirname;
use function rmdir;
use function sys_get_temp_dir;

/**
 * EnvironmentHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(EnvironmentHandler::class)]
#[CoversClass(ApplicationContextHandler::class)]
#[CoversClass(WithEnvironment::class)]
#[CoversClass(InApplicationContext::class)]
final class EnvironmentHandlerTest extends TestCase
{
    #[Test]
    public function failsWhenTheProjectDirectoryCannotBeCreated(): void
    {
        // "/dev/null" is a file, so mkdir() below it is guaranteed to fail.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(1752561604);

        (new EnvironmentHandler())->apply(new WithEnvironment(temporaryProjectPath: false, projectPath: '/dev/null/ttt'));
    }

    #[Test]
    public function cleanupToleratesAnAlreadyRemovedProjectDirectory(): void
    {
        $restore = (new EnvironmentHandler())->apply(new WithEnvironment(context: 'Testing'));
        $projectPath = Environment::getProjectPath();

        // Remove the sandbox out from under the restorer to exercise its "already gone" guard.
        foreach (['public', 'var', 'config'] as $directory) {
            @rmdir($projectPath.'/'.$directory);
        }
        @rmdir($projectPath);
        self::assertDirectoryDoesNotExist($projectPath);

        $restore();

        self::assertDirectoryDoesNotExist($projectPath);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function usesTheSystemTemporaryDirectoryAsProjectRootWhenRequested(): void
    {
        $temp = sys_get_temp_dir();

        $restore = (new EnvironmentHandler())->apply(new WithEnvironment(temporaryProjectPath: false));

        self::assertSame($temp, Environment::getProjectPath());

        $restore();

        // The system temp root is reused (not created by us); only prune the empty scaffolding.
        foreach (['public', 'var', 'config'] as $directory) {
            @rmdir($temp.'/'.$directory);
        }
    }

    #[Test]
    public function bootstrapsTemporaryProjectAndCleansUp(): void
    {
        $restore = (new EnvironmentHandler())->apply(new WithEnvironment(context: 'Testing'));

        $projectPath = Environment::getProjectPath();

        self::assertDirectoryExists($projectPath.'/var');
        self::assertSame('Testing', (string) Environment::getContext());

        $restore();

        self::assertDirectoryDoesNotExist($projectPath);
    }

    #[Test]
    public function cleanupDoesNotFollowSymlinksOutOfTheSandbox(): void
    {
        $outside = sys_get_temp_dir().'/ttt-outside-'.bin2hex(random_bytes(8));
        mkdir($outside, 0o700, true);
        file_put_contents($outside.'/sentinel.txt', 'must survive');

        $restore = (new EnvironmentHandler())->apply(new WithEnvironment(context: 'Testing'));
        symlink($outside, Environment::getVarPath().'/link-to-outside');

        $restore();

        self::assertFileExists($outside.'/sentinel.txt');

        unlink($outside.'/sentinel.txt');
        rmdir($outside);
    }

    #[Test]
    public function resolvesProjectPathToConsumingPackageRootWhenSelfSentinelIsUsed(): void
    {
        $restore = (new EnvironmentHandler())->apply(new WithEnvironment(temporaryProjectPath: false, projectPath: 'self'));

        self::assertSame(dirname(__DIR__, 3), Environment::getProjectPath());

        $restore();
    }

    #[Test]
    public function applicationContextHandlerSwitchesAndRestoresContext(): void
    {
        $restoreEnvironment = (new EnvironmentHandler())->apply(new WithEnvironment(context: 'Testing'));

        $restoreContext = (new ApplicationContextHandler())->apply(new InApplicationContext('Development'));
        self::assertSame('Development', (string) Environment::getContext());

        $restoreContext();
        self::assertSame('Testing', (string) Environment::getContext());

        $restoreEnvironment();
    }
}

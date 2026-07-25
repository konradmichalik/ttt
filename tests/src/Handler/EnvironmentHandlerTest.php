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
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;

/**
 * EnvironmentHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(EnvironmentHandler::class)]
#[CoversClass(ApplicationContextHandler::class)]
final class EnvironmentHandlerTest extends TestCase
{
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

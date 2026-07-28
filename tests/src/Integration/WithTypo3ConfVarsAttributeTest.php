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

namespace KonradMichalik\Ttt\Tests\Integration;

use KonradMichalik\Ttt\Attribute\{Typo3ConfVarsSentinel, WithEnvironment, WithEnvVar, WithGlobal, WithTypo3ConfVars};
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;

use function dirname;

/**
 * WithTypo3ConfVarsAttributeTest.
 *
 * End-to-end proof that the TttExtension (registered in phpunit.xml)
 * applies attributes before setUp() and restores state after each test. The
 * unannotated test verifies restoration independent of execution order, as
 * the process-wide baseline contains no TYPO3_CONF_VARS.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[WithTypo3ConfVars(['EXTCONF' => ['terrarium' => ['classLevel' => true]]])]
final class WithTypo3ConfVarsAttributeTest extends TestCase
{
    private mixed $observedInSetUp = null;

    protected function setUp(): void
    {
        $this->observedInSetUp = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['terrarium']['classLevel'] ?? null;
    }

    #[Test]
    public function classLevelAttributeIsAppliedBeforeSetUp(): void
    {
        self::assertTrue($this->observedInSetUp);
    }

    #[Test]
    #[WithTypo3ConfVars(['EXTCONF' => ['terrarium' => ['methodLevel' => 'wins']]])]
    public function methodLevelAttributeMergesOnTopOfClassLevel(): void
    {
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['terrarium']['classLevel']);
        self::assertSame('wins', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['terrarium']['methodLevel']);
    }

    #[Test]
    #[WithTypo3ConfVars(['EXTCONF' => ['terrarium' => ['classLevel' => Typo3ConfVarsSentinel::Unset]]])]
    public function methodLevelSentinelClearsClassLevelKey(): void
    {
        self::assertArrayNotHasKey('classLevel', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['terrarium']);
    }

    #[Test]
    #[WithEnvVar('TTT_INTEGRATION_VAR', '1')]
    public function envVarAttributeIsApplied(): void
    {
        self::assertSame('1', getenv('TTT_INTEGRATION_VAR'));
    }

    #[Test]
    #[WithGlobal('TTT_INTEGRATION_GLOBAL', 'set')]
    public function globalAttributeIsApplied(): void
    {
        self::assertSame('set', $GLOBALS['TTT_INTEGRATION_GLOBAL']);
    }

    #[Test]
    #[WithEnvironment(temporaryProjectPath: false, projectPath: 'self')]
    public function environmentAttributeResolvesSelfProjectPathBeforeSetUp(): void
    {
        self::assertSame(dirname(__DIR__, 3), Environment::getProjectPath());
    }
}

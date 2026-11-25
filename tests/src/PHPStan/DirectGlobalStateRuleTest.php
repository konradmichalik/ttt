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

namespace KonradMichalik\Ttt\Tests\PHPStan;

use KonradMichalik\Ttt\PHPStan\DirectGlobalStateRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\{CoversClass, Test};

use function sprintf;

/**
 * DirectGlobalStateRuleTest.
 *
 * @extends RuleTestCase<DirectGlobalStateRule>
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(DirectGlobalStateRule::class)]
final class DirectGlobalStateRuleTest extends RuleTestCase
{
    private const FIXTURE = __DIR__.'/../../fixtures/phpstan/direct-global-state.php';

    #[Test]
    public function pointsEachDirectChangeToTheMatchingAttribute(): void
    {
        $this->analyse([self::FIXTURE], [
            [self::message('$GLOBALS[\'TYPO3_CONF_VARS\'] assignment'), self::lineOf('$GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'sitename\']'), self::tip('WithTypo3ConfVars')],
            [self::message('$GLOBALS[\'BE_USER\'] assignment'), self::lineOf('$GLOBALS[\'BE_USER\']'), self::tip('WithBackendUser')],
            [self::message('$GLOBALS[\'TCA\'] assignment'), self::lineOf('$GLOBALS[\'TCA\']'), self::tip('WithTca')],
            [self::message('$GLOBALS[\'LANG\'] assignment'), self::lineOf('$GLOBALS[\'LANG\']'), self::tip('WithGlobal')],
            [self::message('$GLOBALS[...] assignment'), self::lineOf('$GLOBALS[$key]'), self::tip('WithGlobal')],
            [self::message('putenv()'), self::lineOf('putenv(\'FOO=bar\')'), self::tip('WithEnvVar')],
            [self::message('GeneralUtility::setSingletonInstance()'), self::lineOf('GeneralUtility::setSingletonInstance('), self::tip('WithSingleton')],
            [self::message('GeneralUtility::addInstance()'), self::lineOf('GeneralUtility::addInstance(stdClass'), self::tip('WithInstance')],
        ]);
    }

    #[Test]
    public function ignoresClassesThatAreNoTestCases(): void
    {
        $this->analyse([__DIR__.'/../../fixtures/phpstan/not-a-test.php'], []);
    }

    #[Test]
    public function ignoresTheTestsOfTttItself(): void
    {
        $this->analyse([__DIR__.'/../../fixtures/phpstan/own-tests.php'], []);
    }

    protected function getRule(): Rule
    {
        return new DirectGlobalStateRule();
    }

    private static function lineOf(string $needle): int
    {
        foreach (file(self::FIXTURE, \FILE_IGNORE_NEW_LINES) ?: [] as $index => $line) {
            if (str_contains($line, $needle)) {
                return $index + 1;
            }
        }

        self::fail(sprintf('Needle "%s" not found in the fixture.', $needle));
    }

    private static function message(string $subject): string
    {
        return sprintf('Test changes global state directly: %s.', $subject);
    }

    private static function tip(string $attribute): string
    {
        return sprintf('Use #[%s] instead, it restores the state after the test. See docs/phpstan-rule.md of konradmichalik/ttt.', $attribute);
    }
}

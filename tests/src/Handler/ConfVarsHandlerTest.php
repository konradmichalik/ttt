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

use Generator;
use KonradMichalik\Ttt\Attribute\{Typo3ConfVarsSentinel, WithTypo3ConfVars};
use KonradMichalik\Ttt\Handler\ConfVarsHandler;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;

/**
 * ConfVarsHandlerTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(ConfVarsHandler::class)]
final class ConfVarsHandlerTest extends TestCase
{
    private ConfVarsHandler $subject;

    protected function setUp(): void
    {
        $this->subject = new ConfVarsHandler();
        unset($GLOBALS['TYPO3_CONF_VARS']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']);
    }

    #[Test]
    public function supportsOnlyWithTypo3ConfVars(): void
    {
        self::assertTrue($this->subject->supports(new WithTypo3ConfVars([])));
    }

    /**
     * @param array<string, mixed> $existing
     * @param array<string, mixed> $override
     * @param array<string, mixed> $expected
     */
    #[Test]
    #[DataProvider('mergeCases')]
    public function appliesConfigurationByDeepMerge(array $existing, array $override, array $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $existing;

        $this->subject->apply(new WithTypo3ConfVars($override));

        self::assertSame($expected, $GLOBALS['TYPO3_CONF_VARS']);
    }

    /**
     * @return Generator<string, array{array<string, mixed>, array<string, mixed>, array<string, mixed>}>
     */
    public static function mergeCases(): Generator
    {
        yield 'adds new top-level key' => [
            ['SYS' => ['sitename' => 'Base']],
            ['EXTCONF' => ['my_ext' => true]],
            ['SYS' => ['sitename' => 'Base'], 'EXTCONF' => ['my_ext' => true]],
        ];

        yield 'deep-merges nested arrays' => [
            ['EXTCONF' => ['my_ext' => ['a' => 1]]],
            ['EXTCONF' => ['my_ext' => ['b' => 2]]],
            ['EXTCONF' => ['my_ext' => ['a' => 1, 'b' => 2]]],
        ];

        yield 'scalar override wins' => [
            ['SYS' => ['sitename' => 'Base']],
            ['SYS' => ['sitename' => 'Override']],
            ['SYS' => ['sitename' => 'Override']],
        ];

        yield 'array replaces scalar' => [
            ['SYS' => ['features' => 'off']],
            ['SYS' => ['features' => ['flag' => true]]],
            ['SYS' => ['features' => ['flag' => true]]],
        ];

        yield 'sentinel clears a nested key set by an earlier merge' => [
            ['EXTCONF' => ['my_ext' => ['configuration' => ['color' => '#f00']]]],
            ['EXTCONF' => ['my_ext' => ['configuration' => Typo3ConfVarsSentinel::Unset]]],
            ['EXTCONF' => ['my_ext' => []]],
        ];

        yield 'sentinel clears a top-level key' => [
            ['SYS' => ['sitename' => 'Base']],
            ['SYS' => Typo3ConfVarsSentinel::Unset],
            [],
        ];

        yield 'sentinel on an absent key is a no-op' => [
            ['SYS' => ['sitename' => 'Base']],
            ['EXTCONF' => Typo3ConfVarsSentinel::Unset],
            ['SYS' => ['sitename' => 'Base']],
        ];
    }

    #[Test]
    public function restorerRevertsToPreviousState(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = ['SYS' => ['sitename' => 'Base']];

        $restore = $this->subject->apply(new WithTypo3ConfVars(['SYS' => ['sitename' => 'Changed']]));
        $restore();

        self::assertSame(['SYS' => ['sitename' => 'Base']], $GLOBALS['TYPO3_CONF_VARS']);
    }

    #[Test]
    public function restorerUnsetsGlobalThatDidNotExistBefore(): void
    {
        $restore = $this->subject->apply(new WithTypo3ConfVars(['SYS' => []]));
        $restore();

        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }
}

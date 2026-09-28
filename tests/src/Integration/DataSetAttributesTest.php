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

use KonradMichalik\Ttt\Attribute\{InApplicationContext, WithBackendUser, WithEnvVar, WithEnvironment, WithTypo3ConfVars};
use PHPUnit\Framework\Attributes\{DataProvider, Test, TestWith};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;

use function getenv;

/**
 * DataSetAttributesTest.
 *
 * Terrarium attributes placed inside a data set apply only to the run of
 * that data set, on top of the class- and method-level attributes.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class DataSetAttributesTest extends TestCase
{
    #[Test]
    #[WithEnvVar('TTT_VARIANT_BASE', 'every run')]
    #[TestWith([new WithEnvVar('TTT_VARIANT', 'dev'), 'dev'])]
    #[TestWith([new WithEnvVar('TTT_VARIANT', 'prod'), 'prod'])]
    public function appliesEnvVarVariantsOnTopOfMethodLevelAttributes(WithEnvVar $variant, string $expected): void
    {
        self::assertSame('every run', getenv('TTT_VARIANT_BASE'));
        self::assertSame($expected, getenv('TTT_VARIANT'));
    }

    #[Test]
    #[WithEnvironment]
    #[TestWith([new InApplicationContext('Development'), true])]
    #[TestWith([new InApplicationContext('Production'), false])]
    public function appliesApplicationContextVariants(InApplicationContext $context, bool $isDevelopment): void
    {
        self::assertSame($isDevelopment, Environment::getContext()->isDevelopment());
        self::assertSame($context->context, (string) Environment::getContext());
    }

    #[Test]
    #[TestWith([new WithBackendUser(admin: true), true])]
    #[TestWith([new WithBackendUser(groups: [2]), false])]
    public function appliesBackendUserVariants(WithBackendUser $user, bool $expectedAdmin): void
    {
        self::assertSame($expectedAdmin, $GLOBALS['BE_USER']->isAdmin());
    }

    /**
     * @return iterable<array{WithEnvVar, WithTypo3ConfVars, string}>
     */
    public static function seasons(): iterable
    {
        yield [new WithEnvVar('TTT_VARIANT', 'christmas'), new WithTypo3ConfVars(['EXTCONF' => ['terrarium' => ['xmas' => true]]]), 'christmas'];
        yield [new WithEnvVar('TTT_VARIANT', 'summer'), new WithTypo3ConfVars(['EXTCONF' => ['terrarium' => ['xmas' => false]]]), 'summer'];
    }

    #[Test]
    #[DataProvider('seasons')]
    public function appliesSeveralAttributesPerDataProviderVariant(WithEnvVar $envVar, WithTypo3ConfVars $confVars, string $season): void
    {
        self::assertSame($season, getenv('TTT_VARIANT'));
        self::assertSame('christmas' === $season, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['terrarium']['xmas']);
    }

    #[Test]
    #[WithEnvVar('TTT_VARIANT', 'method')]
    #[WithTypo3ConfVars(['EXTCONF' => ['terrarium' => ['source' => 'method', 'methodOnly' => true]]])]
    #[TestWith([new WithEnvVar('TTT_VARIANT', 'data set'), new WithTypo3ConfVars(['EXTCONF' => ['terrarium' => ['source' => 'data set']]])])]
    public function dataSetAttributesTakePrecedenceOverMethodLevelAttributes(WithEnvVar $envVar, WithTypo3ConfVars $confVars): void
    {
        self::assertSame('data set', getenv('TTT_VARIANT'));
        self::assertSame('data set', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['terrarium']['source']);
        self::assertTrue($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['terrarium']['methodOnly']);
    }

    #[Test]
    #[TestWith(['plain value', 42])]
    public function ignoresDataSetsWithoutTerrariumAttributes(string $value, int $number): void
    {
        self::assertFalse(getenv('TTT_VARIANT'));
        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }
}

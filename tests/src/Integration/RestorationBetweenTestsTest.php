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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * RestorationBetweenTestsTest.
 *
 * Companion to WithTypo3ConfVarsAttributeTest: this class carries NO
 * Terrarium attributes, so any leaked TYPO3_CONF_VARS or environment
 * variable from the annotated tests would fail here.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class RestorationBetweenTestsTest extends TestCase
{
    #[Test]
    public function confVarsGlobalIsNotLeakedByAnnotatedTests(): void
    {
        self::assertArrayNotHasKey('TYPO3_CONF_VARS', $GLOBALS);
    }

    #[Test]
    public function envVarIsNotLeakedByAnnotatedTests(): void
    {
        self::assertFalse(\getenv('TTT_INTEGRATION_VAR'));
    }
}

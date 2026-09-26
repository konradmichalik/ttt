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

namespace KonradMichalik\Ttt\Tests\Fixtures\DataSet;

use KonradMichalik\Ttt\Attribute\WithEnvVar;
use PHPUnit\Framework\Attributes\{Test, TestWith};
use PHPUnit\Framework\TestCase;

use function getenv;

/**
 * FailingVariantFixtureTest.
 *
 * Executed by FailingVariantRestorationTest in a separate PHPUnit run, never
 * by the main suite: the first variant fails on purpose.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class FailingVariantFixtureTest extends TestCase
{
    #[Test]
    #[TestWith([new WithEnvVar('TTT_FAILING_VARIANT', 'first'), true])]
    #[TestWith([new WithEnvVar('TTT_PASSING_VARIANT', 'second'), false])]
    public function variant(WithEnvVar $variant, bool $fail): void
    {
        if ($fail) {
            self::fail('The first variant fails on purpose.');
        }

        self::assertFalse(getenv('TTT_FAILING_VARIANT'), 'The failing variant leaked into the next one.');
        self::assertSame('second', getenv('TTT_PASSING_VARIANT'));
    }
}

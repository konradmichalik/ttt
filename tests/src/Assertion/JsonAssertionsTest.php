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

namespace KonradMichalik\Ttt\Tests\Assertion;

use KonradMichalik\Ttt\Assertion\JsonAssertions;
use PHPUnit\Framework\{AssertionFailedError, TestCase};
use PHPUnit\Framework\Attributes\{CoversTrait, Test};

/**
 * JsonAssertionsTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversTrait(JsonAssertions::class)]
final class JsonAssertionsTest extends TestCase
{
    use JsonAssertions;

    private const JSON = '{"result":{"items":[{"uid":42,"title":"Terrarium"}]}}';

    #[Test]
    public function assertsValueAtDotPathOnJsonString(): void
    {
        self::assertJsonPath(self::JSON, 'result.items.0.uid', 42);
    }

    #[Test]
    public function assertsValueAtDotPathOnDecodedArray(): void
    {
        self::assertJsonPath(['a' => ['b' => 'c']], 'a.b', 'c');
    }

    #[Test]
    public function assertsPresenceOfMultiplePaths(): void
    {
        self::assertJsonHasPaths(self::JSON, ['result', 'result.items.0.title']);
    }

    #[Test]
    public function failsWithMissingSegmentInformation(): void
    {
        try {
            self::assertJsonHasPath(self::JSON, 'result.missing.deep');
            self::fail('Expected assertion failure.');
        } catch (AssertionFailedError $error) {
            self::assertStringContainsString('missing segment "missing"', $error->getMessage());
        }
    }
}

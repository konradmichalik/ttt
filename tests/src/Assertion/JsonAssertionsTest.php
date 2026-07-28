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

    #[Test]
    public function assertJsonPathFailsWhenTheValueIsMissing(): void
    {
        try {
            self::assertJsonPath(self::JSON, 'result.items.5.uid', 1);
            self::fail('Expected assertion failure.');
        } catch (AssertionFailedError $error) {
            self::assertStringContainsString('does not exist', $error->getMessage());
        }
    }

    #[Test]
    public function assertsAbsenceOfPath(): void
    {
        self::assertJsonMissingPath(self::JSON, 'result.servers');
    }

    #[Test]
    public function assertJsonMissingPathFailsWhenThePathExists(): void
    {
        try {
            self::assertJsonMissingPath(self::JSON, 'result.items.0.uid');
            self::fail('Expected assertion failure.');
        } catch (AssertionFailedError $error) {
            self::assertStringContainsString('JSON path "result.items.0.uid" unexpectedly exists', $error->getMessage());
        }
    }

    #[Test]
    public function assertsAbsenceOfMultiplePaths(): void
    {
        self::assertJsonMissingPaths(self::JSON, ['result.servers', 'result.errors']);
    }

    #[Test]
    public function assertsFloatValueAtDotPathWithinDelta(): void
    {
        self::assertJsonPathEqualsWithDelta(['ratio' => 0.6667], 'ratio', 0.667, 0.001);
    }

    #[Test]
    public function assertJsonPathEqualsWithDeltaFailsWhenOutsideDelta(): void
    {
        try {
            self::assertJsonPathEqualsWithDelta(['ratio' => 0.5], 'ratio', 0.667, 0.001);
            self::fail('Expected assertion failure.');
        } catch (AssertionFailedError $error) {
            self::assertStringContainsString('Failed asserting JSON path "ratio"', $error->getMessage());
        }
    }

    #[Test]
    public function assertJsonPathEqualsWithDeltaFailsWhenTheValueIsMissing(): void
    {
        try {
            self::assertJsonPathEqualsWithDelta(self::JSON, 'result.items.5.uid', 1.0, 0.1);
            self::fail('Expected assertion failure.');
        } catch (AssertionFailedError $error) {
            self::assertStringContainsString('does not exist', $error->getMessage());
        }
    }

    #[Test]
    public function assertJsonMissingPathsFailsWhenOnePathExists(): void
    {
        try {
            self::assertJsonMissingPaths(self::JSON, ['result.servers', 'result.items']);
            self::fail('Expected assertion failure.');
        } catch (AssertionFailedError $error) {
            self::assertStringContainsString('JSON path "result.items" unexpectedly exists', $error->getMessage());
        }
    }
}

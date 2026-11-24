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

namespace KonradMichalik\Ttt\Tests\Fixture;

use KonradMichalik\Ttt\Fixture\JsonResponseFixture;
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;

use function json_decode;

/**
 * JsonResponseFixtureTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(JsonResponseFixture::class)]
final class JsonResponseFixtureTest extends TestCase
{
    #[Test]
    public function bodyContainsThePayloadAsJson(): void
    {
        $response = JsonResponseFixture::create(['numFound' => 3, 'docs' => []]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['numFound' => 3, 'docs' => []], json_decode((string) $response->getBody(), true));
    }

    #[Test]
    public function declaresAJsonContentType(): void
    {
        $response = JsonResponseFixture::create([]);

        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function usesTheGivenStatusCode(): void
    {
        self::assertSame(503, JsonResponseFixture::create(['error' => 'down'], 503)->getStatusCode());
    }
}

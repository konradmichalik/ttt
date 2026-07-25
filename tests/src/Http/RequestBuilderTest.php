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

namespace KonradMichalik\Ttt\Tests\Http;

use KonradMichalik\Ttt\Http\{RequestBuilder, Requests};
use PHPUnit\Framework\Attributes\{CoversClass, Test};
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Http\NormalizedParams;

/**
 * RequestBuilderTest.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
#[CoversClass(RequestBuilder::class)]
#[CoversClass(Requests::class)]
final class RequestBuilderTest extends TestCase
{
    #[Test]
    public function buildsRequestWithMethodUriAndNormalizedParams(): void
    {
        $request = Requests::get('https://example.com/api/count')
            ->withRemoteAddress('10.0.0.1')
            ->build();

        self::assertSame('GET', $request->getMethod());
        self::assertSame('/api/count', $request->getUri()->getPath());

        $normalizedParams = $request->getAttribute('normalizedParams');
        self::assertInstanceOf(NormalizedParams::class, $normalizedParams);
        self::assertSame('10.0.0.1', $normalizedParams->getRemoteAddress());
    }

    #[Test]
    public function buildsJsonBodyWithContentTypeHeader(): void
    {
        $request = Requests::post('/api/items')
            ->withJsonBody(['title' => 'Terrarium'])
            ->build();

        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame('{"title":"Terrarium"}', (string) $request->getBody());
    }

    #[Test]
    public function appliesQueryParamsParsedBodyAndAttributes(): void
    {
        $request = Requests::put('/api/items/1')
            ->withQueryParams(['force' => '1'])
            ->withParsedBody(['title' => 'Updated'])
            ->withAttribute('custom', 'value')
            ->withoutNormalizedParams()
            ->build();

        self::assertSame(['force' => '1'], $request->getQueryParams());
        self::assertSame(['title' => 'Updated'], $request->getParsedBody());
        self::assertSame('value', $request->getAttribute('custom'));
        self::assertNull($request->getAttribute('normalizedParams'));
    }
}

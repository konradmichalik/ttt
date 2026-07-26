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
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test};
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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

    #[Test]
    public function appliesCustomHeaderAndServerParam(): void
    {
        $request = Requests::get('/api/count')
            ->withHeader('X-Api-Key', 'secret')
            ->withServerParam('HTTP_X_FORWARDED_FOR', '203.0.113.1')
            ->withoutNormalizedParams()
            ->build();

        self::assertSame('secret', $request->getHeaderLine('X-Api-Key'));
        self::assertSame('203.0.113.1', $request->getServerParams()['HTTP_X_FORWARDED_FOR']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function factoryMethodProvider(): iterable
    {
        yield 'patch' => ['patch', 'PATCH'];
        yield 'delete' => ['delete', 'DELETE'];
    }

    #[Test]
    #[DataProvider('factoryMethodProvider')]
    public function exposesFactoryForEveryHttpMethod(string $factory, string $expectedMethod): void
    {
        $request = Requests::{$factory}('/api/items/1')->withoutNormalizedParams()->build();

        self::assertSame($expectedMethod, $request->getMethod());
    }

    #[Test]
    public function requestsFactoryIsNotInstantiable(): void
    {
        $constructor = (new ReflectionClass(Requests::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate(), 'Requests must be a static-only factory.');
    }
}

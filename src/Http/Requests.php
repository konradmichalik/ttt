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

namespace KonradMichalik\Ttt\Http;

/**
 * Requests.
 *
 * Entry point of the request kit: fluent builder for TYPO3 ServerRequest
 * instances with sensible defaults, replacing hand-written
 * "new ServerRequest(...)" constructions in tests.
 *
 * <code>
 * $request = Requests::get('/api/count')
 *     ->withJsonBody(['q' => 'x'])
 *     ->withRemoteAddress('10.0.0.1')
 *     ->build();
 * </code>
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class Requests
{
    // Static-only factory: the constructor exists solely to forbid instantiation.
    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    public static function get(string $uri): RequestBuilder
    {
        return new RequestBuilder('GET', $uri);
    }

    public static function post(string $uri): RequestBuilder
    {
        return new RequestBuilder('POST', $uri);
    }

    public static function put(string $uri): RequestBuilder
    {
        return new RequestBuilder('PUT', $uri);
    }

    public static function patch(string $uri): RequestBuilder
    {
        return new RequestBuilder('PATCH', $uri);
    }

    public static function delete(string $uri): RequestBuilder
    {
        return new RequestBuilder('DELETE', $uri);
    }
}

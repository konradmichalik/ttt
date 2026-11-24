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

namespace KonradMichalik\Ttt\Fixture;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * JsonResponseFixture.
 *
 * Builds a real PSR-7 JSON response for code that consumes an HTTP client
 * response - shared replacement for per-repository helpers mocking
 * StreamInterface::__toString() and ResponseInterface.
 *
 * Requires typo3/cms-core.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class JsonResponseFixture
{
    // Static-only utility: the constructor exists solely to forbid instantiation.
    // @codeCoverageIgnoreStart
    private function __construct() {}
    // @codeCoverageIgnoreEnd

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function create(array $payload, int $status = 200): ResponseInterface
    {
        return new JsonResponse($payload, $status);
    }
}

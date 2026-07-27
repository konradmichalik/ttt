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

use TypeError;
use TYPO3\CMS\Core\Http\{NormalizedParams, ServerRequest, Stream};

use function json_encode;

/**
 * RequestBuilder.
 *
 * Immutable-ish fluent builder producing a TYPO3 ServerRequest including a
 * derived "normalizedParams" attribute. All with*() methods return $this for
 * chaining; build() may be called multiple times.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
final class RequestBuilder
{
    /** @var array<string, string> */
    private array $headers = [];

    /** @var array<string, mixed> */
    private array $serverParams = [];

    /** @var array<string, mixed> */
    private array $queryParams = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<string, mixed>|null */
    private ?array $parsedBody = null;

    private ?string $rawBody = null;

    private bool $withNormalizedParams = true;

    public function __construct(
        private readonly string $method,
        private readonly string $uri,
    ) {}

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $body
     */
    public function withJsonBody(array $body): self
    {
        $this->rawBody = json_encode($body, \JSON_THROW_ON_ERROR);
        $this->headers['Content-Type'] = 'application/json';

        return $this;
    }

    /**
     * @param array<string, mixed> $parsedBody
     */
    public function withParsedBody(array $parsedBody): self
    {
        $this->parsedBody = $parsedBody;

        return $this;
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public function withQueryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    public function withRemoteAddress(string $remoteAddress): self
    {
        $this->serverParams['REMOTE_ADDR'] = $remoteAddress;

        return $this;
    }

    public function withServerParam(string $name, mixed $value): self
    {
        $this->serverParams[$name] = $value;

        return $this;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function withoutNormalizedParams(): self
    {
        $this->withNormalizedParams = false;

        return $this;
    }

    public function build(): ServerRequest
    {
        $request = new ServerRequest($this->uri, $this->method, 'php://input', $this->headers, $this->serverParams);

        if (null !== $this->rawBody) {
            $stream = new Stream('php://temp', 'rw');
            $stream->write($this->rawBody);
            $stream->rewind();
            $request = $request->withBody($stream);
        }

        if (null !== $this->parsedBody) {
            $request = $request->withParsedBody($this->parsedBody);
        }

        if ([] !== $this->queryParams) {
            $request = $request->withQueryParams($this->queryParams);
        }

        foreach ($this->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        if ($this->withNormalizedParams && !isset($this->attributes['normalizedParams'])) {
            try {
                $request = $request->withAttribute(
                    'normalizedParams',
                    NormalizedParams::createFromRequest($request, $GLOBALS['TYPO3_CONF_VARS']['SYS'] ?? []),
                );
            } catch (TypeError) {
                // TYPO3\CMS\Core\Core\Environment was never initialized; normalizedParams cannot be derived.
            }
        }

        return $request;
    }
}

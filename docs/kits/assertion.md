# [Assertion kit](../../src/Assertion/JsonAssertions.php)

A trait ([`JsonAssertions`](../../src/Assertion/JsonAssertions.php)) with dot-path based assertions for JSON strings or already decoded arrays. Failure messages name the first missing path segment. Intended for MCP responses, OpenAPI output and other machine-readable artifacts.

## Example

```php
use KonradMichalik\Ttt\Assertion\JsonAssertions;

final class McpResponseTest extends TestCase
{
    use JsonAssertions;

    #[Test]
    public function returnsItems(): void
    {
        self::assertJsonPath($response, 'result.items.0.uid', 42);
        self::assertJsonHasPaths($response, ['result', 'result.items']);
        self::assertJsonPathEqualsWithDelta($response, 'result.hitRatio', 0.667, 0.001);
    }
}
```

<details>
<summary>More examples</summary>

### Asserting a path is absent

```php
self::assertJsonMissingPath($response, 'result.error');
self::assertJsonMissingPaths($response, ['result.error', 'result.warnings']);
```

### Asserting against a raw JSON string

`$json` may be a raw JSON string instead of an already-decoded array: it is decoded internally:

```php
$json = '{"result":{"items":[{"uid":42}]}}';

self::assertJsonPath($json, 'result.items.0.uid', 42);
```

### Failure message on a missing path

```php
self::assertJsonPath($response, 'result.items.0.title', 'Terrarium');
// Fails with: JSON path "result.items.0.title" does not exist (missing segment "title").
```

</details>

## Migrating from hand-written code

**Before:**

```php
$decoded = json_decode((string) $response->getBody(), true);
self::assertSame(42, $decoded['result']['items'][0]['uid']);
```

**After:**

```php
use KonradMichalik\Ttt\Assertion\JsonAssertions;

final class McpResponseTest extends TestCase
{
    use JsonAssertions;

    #[Test]
    public function returnsItems(): void
    {
        self::assertJsonPath((string) $response->getBody(), 'result.items.0.uid', 42);
        self::assertJsonHasPaths($response->getBody(), ['result.schemaVersion', 'result.items']);
    }
}
```

For missing paths the failure message names the first missing segment, a much faster diagnosis than an `Undefined array key`. To assert a path is deliberately **absent** (e.g. an OpenAPI document omitting `servers` for a root-mounted site), use `assertJsonMissingPath()`/`assertJsonMissingPaths()` instead of a manual `assertArrayNotHasKey()` fallback.

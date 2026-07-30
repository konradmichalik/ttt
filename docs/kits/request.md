# [Request kit](../../src/Http/Requests.php)

A fluent builder for TYPO3 `ServerRequest` objects: [`Requests`](../../src/Http/Requests.php) is the entry point (`get()`/`post()`/`put()`/`patch()`/`delete()`), returning a [`RequestBuilder`](../../src/Http/RequestBuilder.php) that auto-sets `normalizedParams`, and supports JSON bodies, query params and custom attributes (e.g. `site`).

## Example

```php
use KonradMichalik\Ttt\Http\Requests;

$request = Requests::post('/api/items')
    ->withJsonBody(['title' => 'Terrarium'])
    ->withRemoteAddress('10.0.0.1')
    ->build(); // TYPO3 ServerRequest incl. normalizedParams attribute
```

<details>
<summary>More examples</summary>

### Query parameters and a custom header

```php
$request = Requests::get('/api/items')
    ->withQueryParams(['page' => '2', 'limit' => '25'])
    ->withHeader('X-Requested-With', 'XMLHttpRequest')
    ->build();
```

### Already-parsed body instead of raw JSON

```php
$request = Requests::put('/api/items/42')
    ->withParsedBody(['title' => 'Updated title'])
    ->build();
```

### `site` attribute with real `SiteSettings`

```php
$request = Requests::get('/')
    ->withSiteSettings(['websiteTitle' => 'My Site'])
    ->build(); // withAttribute('site', ...): getSettings() returns a real SiteSettings instance
```

### Opting out of `normalizedParams`

```php
$request = Requests::get('/api/items')
    ->withoutNormalizedParams()
    ->build();
```

</details>

## Migrating from hand-written code

**Before:**

```php
$request = (new ServerRequest('https://example.com/api/count', 'GET'))
    ->withQueryParams(['q' => 'x'])
    ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(...));
```

**After:**

```php
use KonradMichalik\Ttt\Http\Requests;

$request = Requests::get('https://example.com/api/count')
    ->withQueryParams(['q' => 'x'])
    ->withRemoteAddress('10.0.0.1')
    ->build();
```

`normalizedParams` is set automatically as an attribute (switchable off via `->withoutNormalizedParams()`), JSON bodies via `->withJsonBody([...])` incl. Content-Type header. For arbitrary site/routing attributes: `->withAttribute('site', $site)`.

### Site settings (extensions reading TypoScript Site Settings)

**Before:**

```php
private function createRequest(array $settings): ServerRequestInterface
{
    $siteSettings = $this->createMock(SiteSettings::class);
    $siteSettings->method('get')->willReturnMap(...);
    $site = $this->createMock(Site::class);
    $site->method('getSettings')->willReturn($siteSettings);

    return (new ServerRequest(...))->withAttribute('site', $site);
}
```

**After:**

```php
$request = Requests::get('/api/count')
    ->withSiteSettings(['maintenance' => false])
    ->build();
```

`withSiteSettings()` builds a *real* `SiteSettings` instance (via `SiteSettings::createFromSettingsTree()`), not a mock: `SiteSettings` is `final readonly` in TYPO3 13.4/14.0 and therefore cannot be mocked with PHPUnit. `get()`/`has()` behave exactly like production, including dot-path lookups for nested settings (`->withSiteSettings(['maintenance' => ['enabled' => true]])` → `get('maintenance.enabled')`). The `Site` object returned as the `'site'` attribute only has `getSettings()` wired up; calling any other `Site` method (`getIdentifier()`, `getRootPageId()`, ...) fails, same "covers the 80% case" scope as [`#[WithBackendUser]`](../attributes/with-backend-user.md).

`Requests`/`RequestBuilder` without an initialized `Environment`: `normalizedParams` is derived via `NormalizedParams::createFromRequest()`, which requires TYPO3's `Environment` to be initialized (e.g. via [`#[WithEnvironment]`](../attributes/with-environment.md)). If it is not, the attribute is silently omitted rather than throwing, equivalent to calling `->withoutNormalizedParams()` explicitly.

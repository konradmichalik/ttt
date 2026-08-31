# [`#[WithCache]`](../../src/Attribute/WithCache.php)

_Scope: Class & Method level · Repeatable_

Registers a cache configuration via `CacheManager::setCacheConfigurations()` for the duration of one test and restores the previous configuration for that identifier afterwards. Defaults to the `"runtime"` cache (`VariableFrontend` backed by `TransientMemoryBackend`), the one `GeneralUtility::xml2array()` (FlexForm parsing) needs and a plain unit test never has registered, since it doesn't bootstrap TYPO3's full cache configuration the way a functional test does.

Requires `typo3/cms-core`.

## Example

```php
#[WithCache]
public function parsesFlexFormXml(): void
{
    // GeneralUtility::xml2array() now works without manual CacheManager setup.
}
```

<details>
<summary>More examples</summary>

### Custom identifier, frontend, or backend

```php
#[WithCache('pages', backend: NullBackend::class)]
public function bypassesThePagesCache(): void {}
```

### Repeatable

```php
#[WithCache]
#[WithCache('pages', backend: NullBackend::class)]
public function registersTwoCaches(): void {}
```

### Existing configurations for other identifiers are preserved

```php
#[WithCache('pages', backend: NullBackend::class)]
public function doesNotDisturbUnrelatedCaches(): void
{
    // Any cache configuration registered before this test (e.g. by a class-level
    // #[WithCache('runtime')]) is still intact; only "pages" is affected.
}
```

</details>

## Migrating from hand-written code

**Before:**

```php
protected function setUp(): void
{
    $cacheManager = new CacheManager();
    $cacheManager->setCacheConfigurations([
        'runtime' => [
            'frontend' => VariableFrontend::class,
            'backend' => TransientMemoryBackend::class,
        ],
    ]);
    GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
}
```

**After:**

```php
use KonradMichalik\Ttt\Attribute\WithCache;

#[Test]
#[WithCache]
public function usesRuntimeCache(): void {}
```

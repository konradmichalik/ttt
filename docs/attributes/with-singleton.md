# [`#[WithSingleton]`](../../src/Attribute/WithSingleton.php)

_Scope: Class & Method level · Repeatable_

Registers a singleton instance via `GeneralUtility::setSingletonInstance()` for the duration of one test and restores the previous singleton map afterwards. The instance may be given as an object (PHP 8.1+ allows `new` in attribute arguments) or as a class-string that will be instantiated without constructor arguments.

Requires `typo3/cms-core`.

## Example

```php
#[WithSingleton(CacheManager::class, new NullCacheManager())]
```

<details>
<summary>More examples</summary>

### Object instance

```php
#[WithSingleton(CacheManager::class, new NullCacheManager())]
public function skipsRealCaching(): void {}
```

### Class-string, instantiated without constructor arguments

```php
#[WithSingleton(NullCacheManager::class, NullCacheManager::class)]
public function skipsRealCaching(): void {}
```

### Repeatable

```php
#[WithSingleton(CacheManager::class, new NullCacheManager())]
#[WithSingleton(LanguageServiceFactory::class, new FakeLanguageServiceFactory())]
public function stubsMultipleSingletons(): void {}
```

</details>

## Migrating from hand-written code

**Before:**

```php
protected function setUp(): void
{
    GeneralUtility::setSingletonInstance(CacheManager::class, new NullCacheManager());
}

protected function tearDown(): void
{
    GeneralUtility::purgeInstances(); // throws away ALL singletons, not just your own
}
```

**After:**

```php
use KonradMichalik\Ttt\Attribute\WithSingleton;

#[Test]
#[WithSingleton(CacheManager::class, new NullCacheManager())]
public function usesInjectedCacheManager(): void {}
```

Two improvements over the hand-rolled pattern: the *complete previous singleton map* is restored (no collateral damage from `purgeInstances()`), and thanks to PHP 8.1+ `new` expressions are allowed directly in the attribute.

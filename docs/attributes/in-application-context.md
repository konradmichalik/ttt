# [`#[InApplicationContext]`](../../src/Attribute/InApplicationContext.php) 🧪

_Scope: Class & Method level_

Switches the TYPO3 application context (e.g. `"Development"`, `"Production/Staging"`) for a single test by re-initializing the `Environment` with an identical state except for the context. Requires an already initialized `Environment`: combine with [`#[WithEnvironment]`](with-environment.md) if needed.

> [!IMPORTANT]
> 🧪 Unit tests only. Fails loudly if used on a `FunctionalTestCase` (the framework already owns `Environment` and the compiled container by the time it would apply).

Requires `typo3/cms-core`.

## Example

```php
#[InApplicationContext('Development')]
public function behavesDifferentlyInDevelopmentContext(): void {}
```

<details>
<summary>More examples</summary>

### Combined with `#[WithEnvironment]`

```php
final class HandlerTest extends TestCase
{
    #[Test]
    #[WithEnvironment]
    #[InApplicationContext('Production/Staging')]
    public function behavesAsStaging(): void
    {
        // Environment::getContext()->isProduction() === true (staging is a Production sub-context).
    }
}
```

### Class level

```php
#[WithEnvironment]
#[InApplicationContext('Development/Debug')]
final class DebugModeTest extends TestCase
{
    #[Test]
    public function showsDebugOutput(): void {}
}
```

</details>

## Migrating from hand-written code

**Before:**

```php
$this->inDevelopmentContext(function (): void {
    self::assertTrue($this->subject->isProfilingActive());
});
```

**After:**

```php
use KonradMichalik\Ttt\Attribute\InApplicationContext;

#[Test]
#[InApplicationContext('Development')]
public function profilingIsActiveInDevelopmentContext(): void
{
    self::assertTrue($this->subject->isProfilingActive());
}
```

The test body becomes flat, with no more closure wrapping. Prerequisite: an initialized `Environment` (in combination, simply put `#[WithEnvironment]` on the class; the handlers run in declaration order, class attributes before method attributes). For scoped switches *within* a test the callable pattern remains available via the [`ApplicationContextSwitcher`](../../src/Traits/ApplicationContextSwitcher.php) trait.

# [`#[WithEnvVar]`](../../src/Attribute/WithEnvVar.php)

_Scope: Class & Method level · Repeatable_

Sets an environment variable (`putenv()`, `$_ENV` and `$_SERVER`) for the duration of a single test and restores the previous state afterwards. Note that `getenv()` calls evaluated at cache-build time (e.g. in `ext_localconf.php`) are **not** affected: this attribute targets per-request evaluations only.

`putenv()`/`$_ENV`/`$_SERVER` are process-global state: safe under `paratest` (one process per worker), unsafe under any runner sharing a process across tests running concurrently.

## Example

```php
#[WithEnvVar('TYPO3_REQUEST_PROFILER_FORCE', '1')]
public function honoursForceFlag(): void {}
```

<details>
<summary>More examples</summary>

### Setting a variable

```php
final class HandlerTest extends TestCase
{
    #[Test]
    #[WithEnvVar('TYPO3_REQUEST_PROFILER_FORCE', '1')]
    public function honoursForceFlag(): void
    {
        // getenv('TYPO3_REQUEST_PROFILER_FORCE') === '1' for the duration of this test.
    }
}
```

### Unsetting a variable

Passing `null` as the value unsets the variable across all three channels instead of setting it, e.g. to test behaviour when a variable is absent:

```php
final class HandlerTest extends TestCase
{
    #[Test]
    #[WithEnvVar('DDEV_APPROOT', null)]
    public function honoursMissingDdevApproot(): void
    {
        // getenv('DDEV_APPROOT') === false for the duration of this test.
    }
}
```

### Repeatable

```php
#[WithEnvVar('FOO', '1')]
#[WithEnvVar('BAR', '2')]
public function honoursMultipleVariables(): void {}
```

</details>

## Migrating from hand-written code

Functional test suites don't run with `TttExtension` enabled, so `#[WithEnvVar]` is unavailable there. The imperative [`EnvVarSandbox`](../../src/Traits/EnvVarSandbox.php) trait covers the same channel restoration for `FunctionalTestCase`:

```php
use KonradMichalik\Ttt\Traits\EnvVarSandbox;

final class SomeFunctionalTest extends FunctionalTestCase
{
    use EnvVarSandbox;

    protected function tearDown(): void
    {
        $this->restoreEnvVars();
        parent::tearDown();
    }

    #[Test]
    public function acceptsTheConfiguredToken(): void
    {
        $this->setEnvVar('MY_EXT_TOKEN', 'secret');
        // ...
    }
}
```

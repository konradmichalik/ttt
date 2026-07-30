# [`#[WithEnvironment]`](../../src/Attribute/WithEnvironment.php) 🧪

_Scope: Class & Method level_

Bootstraps TYPO3's `Environment` for a single test (or test class) via `Environment::initialize()`. By default a temporary project directory (including `public/`, `var/` and `config/`) is created and deleted afterwards. If the `Environment` was initialized before, the previous state is restored; if it was **not** initialized before, it stays initialized with neutral values (un-initializing typed static properties is not possible in PHP).

> [!IMPORTANT]
> 🧪 Unit tests only. Fails loudly if used on a `FunctionalTestCase` (the framework already owns `Environment` and the compiled container by the time it would apply).

Requires `typo3/cms-core`.

## Example

```php
#[WithEnvironment]
public function readsFromEnvironmentProjectPath(): void {}
```

<details>
<summary>More examples</summary>

### Temporary project directory (default)

```php
final class HandlerTest extends TestCase
{
    #[Test]
    #[WithEnvironment]
    public function readsFromEnvironmentProjectPath(): void
    {
        // Environment::getProjectPath() points at a freshly created temp directory, deleted afterwards.
    }
}
```

### Rooted at the consuming package's own directory

Passing the sentinel `'self'` as `projectPath` roots the sandbox at the consuming package's own directory (the one containing its `composer.json`) instead of a temporary directory. Useful when a test resolves absolute asset paths (fonts, fixtures) that must live inside the extension itself:

```php
#[WithEnvironment(projectPath: 'self')]
public function resolvesFixtureRelativeToExtensionRoot(): void {}
```

### Custom context and CLI mode

```php
#[WithEnvironment(context: 'Production', cli: false)]
public function behavesAsProductionWebRequest(): void {}
```

</details>

## Migrating from hand-written code

**Before:**

```php
public static function setUpBeforeClass(): void
{
    $projectPath = sys_get_temp_dir() . '/my-ext-test-' . uniqid();
    mkdir($projectPath . '/var', 0777, true);
    Environment::initialize(
        new ApplicationContext('Testing'),
        true, true,
        $projectPath, $projectPath . '/public', $projectPath . '/var',
        $projectPath . '/config', $projectPath . '/public/index.php', 'UNIX'
    );
}
```

**After:**

```php
use KonradMichalik\Ttt\Attribute\WithEnvironment;

#[WithEnvironment(context: 'Testing')]
final class PathUtilityTest extends TestCase {}
```

If you need a fixed directory instead of a temporary one: `#[WithEnvironment(projectPath: '/path', temporaryProjectPath: false)]`.

If a test resolves an absolute asset path (fonts, image fixtures) that must live *inside the extension itself* via `GeneralUtility::getFileAbsFileName()`, a temporary or otherwise-fixed `projectPath` puts that path outside `Environment::getProjectPath()` and resolution silently returns `''`. Use the `'self'` sentinel instead: it roots the sandbox at the consuming package's own directory (found by walking up from the running test's file to the nearest `composer.json`), so paths inside the extension keep resolving:

```php
#[WithEnvironment(projectPath: 'self', temporaryProjectPath: false)]
final class AbstractRenderingTestCase extends TestCase {}
```

**Caution, semantic change:** `setUpBeforeClass` ran once per class, the attribute runs per test. This is intended (isolation) but costs minimal runtime through mkdir/rmdir. For very large classes with purely read-only access to the paths this is negligible.

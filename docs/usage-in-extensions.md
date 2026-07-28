# Using ttt in your own TYPO3 extensions

Migration and usage guide for adopting `konradmichalik/ttt` (ttt = TYPO3 Testing Terrarium) in the existing extension test suites.

---

## 1. Installation & setup

```bash
composer require --dev konradmichalik/ttt
```

During local development on the package itself (before the first Packagist release) via a path repository:

```json
"repositories": [
    { "type": "path", "url": "../ttt" }
],
"require-dev": {
    "konradmichalik/ttt": "@dev"
}
```

Register the extension once in the extension's PHPUnit configuration (e.g. `Tests/Build/UnitTests.xml` or `phpunit.xml`):

```xml
<extensions>
    <bootstrap class="KonradMichalik\Ttt\TttExtension"/>
</extensions>
```

**Important:** The registration belongs only in the **unit test** configuration. Functional tests keep running unchanged via `typo3/testing-framework` — Terrarium replaces nothing there and disturbs nothing there (the subscribers only react to tests carrying Terrarium attributes).

---

## 2. Migration recipes (before → after)

### 2.1 TYPO3_CONF_VARS setup with tearDown cleanup

The most common pattern across the portfolio (233 occurrences).

**Before:**

```php
protected function setUp(): void
{
    $this->backup = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['my_ext']['configuration'] = ['color' => '#f00'];
}

protected function tearDown(): void
{
    $GLOBALS['TYPO3_CONF_VARS'] = $this->backup;
}
```

**After:**

```php
use KonradMichalik\Ttt\Attribute\WithTypo3ConfVars;

#[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => ['color' => '#f00']]]])]
final class HandlerTest extends TestCase
{
    // setUp/tearDown disappear entirely
}
```

Rules: the class attribute applies to all tests of the class, method attributes merge on top (the method wins on conflicts), the attribute is repeatable. The merge is a deep merge — it is enough to specify the subtree you actually need. Restore is guaranteed to run, even when the test fails hard.

Because the deep merge only *adds onto* an existing array subtree, overriding a key with `[]` is a no-op and can't clear a key set by a class-level attribute (e.g. to simulate the "not configured" case). Use the `Typo3ConfVarsSentinel::Unset` marker instead — it removes the key from the merged result:

```php
use KonradMichalik\Ttt\Attribute\Typo3ConfVarsSentinel;

#[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => ['color' => '#f00']]]])]
final class HandlerTest extends TestCase
{
    #[Test]
    #[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => Typo3ConfVarsSentinel::Unset]]])]
    public function behavesAsUnconfigured(): void { /* ... */ }
}
```

For manipulations **mid-test** (e.g. changing configuration and resolving it again) there is the imperative variant:

```php
use KonradMichalik\Ttt\Traits\ConfVarsSandbox;

final class HandlerTest extends TestCase
{
    use ConfVarsSandbox;

    protected function tearDown(): void
    {
        $this->restoreTypo3ConfVars();
    }

    #[Test]
    public function reactsToChangedConfiguration(): void
    {
        $this->setTypo3ConfVars(['EXTCONF' => ['my_ext' => ['mode' => 'a']]]);
        // ... first assertion ...
        $this->setTypo3ConfVars(['EXTCONF' => ['my_ext' => ['mode' => 'b']]]);
        // ... second assertion ...
    }
}
```

For manipulations mid-test in functional test suites (which don't run with `TttExtension` enabled, so `#[WithEnvVar]` is unavailable there), the same imperative pattern exists for environment variables:

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

### 2.2 Environment::initialize in setUpBeforeClass

The block duplicated 12× (letter-avatar, ai-mate, request-profiler, routing).

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

The temporary project directory (incl. `public/`, `var/`, `config/`) is created per test and deleted afterwards; a previously initialized Environment is restored exactly. If you need a fixed directory: `#[WithEnvironment(projectPath: '/path', temporaryProjectPath: false)]`.

**Caution, semantic change:** `setUpBeforeClass` ran once per class, the attribute runs per test. This is intended (isolation) but costs minimal runtime through mkdir/rmdir. For very large classes with purely read-only access to the paths this is negligible; should it ever become measurable, a class scope can be added to the package.

### 2.3 DevelopmentContextTrait (request-profiler)

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

The test body becomes flat — no more closure wrapping. Prerequisite: an initialized Environment (in combination, simply put `#[WithEnvironment]` on the class; the handlers run in declaration order, class attributes before method attributes). For scoped switches *within* a test the callable pattern remains available via the `ApplicationContextSwitcher` trait.

### 2.4 setSingletonInstance / purgeInstances (request-profiler and others)

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

Two improvements over the hand-rolled pattern: the *complete previous singleton map* is restored (no collateral damage from `purgeInstances()`), and thanks to PHP 8.1+ `new` expressions are allowed directly in the attribute. Alternatively a class string works, instantiated without constructor arguments: `#[WithSingleton(Foo::class, FakeFoo::class)]`.

### 2.5 BE_USER stubs (environment-indicator, file-sync)

**Before:**

```php
$user = $this->createMock(BackendUserAuthentication::class);
$user->method('isAdmin')->willReturn(true);
$GLOBALS['BE_USER'] = $user;
// tearDown: unset($GLOBALS['BE_USER']);
```

**After:**

```php
#[Test]
#[WithBackendUser(admin: true, uid: 42)]
public function showsIndicatorForAdmins(): void {}
```

The stub is a real `BackendUserAuthentication` subclass with a populated `user` array — `isAdmin()`, `$user->user['uid']` etc. work without mock configuration. Where tests need specific mock behavior (e.g. `check()` expectations), stay with the mock; the attribute covers the 80% case "there just needs to be an (admin) user".

For tests that check backend-user-group membership, pass `groups`: `#[WithBackendUser(groups: [3, 7])]` populates `userGroupsUID` accordingly.

### 2.6 Time-dependent tests

**After (newly possible):**

```php
#[Test]
#[FreezeTime('2026-07-14T12:00:00Z')]
public function calculatesExpiryFromNow(): void
{
    // The Context 'date' aspect AND $GLOBALS['EXEC_TIME']/ACCESS_TIME are pinned
}
```

Covers both time sources that TYPO3 code typically uses. Code with raw `time()`/`new \DateTime()` stays unaffected — that code is the actual refactoring candidate (inject the Context aspect).

### 2.7 Hand-built ServerRequests (routing: 54×, request-profiler: 20×)

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

`normalizedParams` is set automatically as an attribute (switchable off via `->withoutNormalizedParams()`), JSON bodies via `->withJsonBody([...])` incl. Content-Type header. For site/routing attributes: `->withAttribute('site', $site)`.

### 2.8 JSON asserts (ai-mate MCP, routing OpenAPI, request-profiler artifacts)

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

For missing paths the failure message names the first missing segment — a much faster diagnosis than an `Undefined array key`. To assert a path is deliberately **absent** (e.g. an OpenAPI document omitting `servers` for a root-mounted site), use `assertJsonMissingPath()`/`assertJsonMissingPaths()` instead of a manual `assertArrayNotHasKey()` fallback.

### 2.9 Validation contracts (environment-indicator: 58 modifier methods)

Instead of hand-written `testValidateConfigurationFailsWithMissingColor()` series:

```php
use KonradMichalik\Ttt\Contract\ConfigurationValidationContract;

final class CircleModifierValidationTest extends ConfigurationValidationContract
{
    protected function isValid(array $configuration): bool
    {
        return (new CircleModifier())->validateConfiguration($configuration);
    }

    protected function validConfiguration(): array
    {
        return ['color' => '#ff0000', 'size' => 0.5];
    }

    protected function schema(): array
    {
        return [
            'color' => 'string',
            'size' => 'float:0..1',
            'position?' => 'enum:top left|top right|bottom left|bottom right',
        ];
    }
}
```

Automatically generated: missing required key, wrong type per key, under-/overshoot per range, and (for `enum`) an unrecognized-value case — a known-but-invalid string like `"top center"` for a `position` key restricted to a fixed set. Add extension-specific special cases (e.g. the hex format of `color`) as additional plain `#[Test]` methods in the same class — the contract is the baseline, not the ceiling.

### 2.10 sys_get_temp_dir handling & fixtures (ai-mate: 17×)

Temp directories for Environment purposes are handled by `#[WithEnvironment]` (incl. cleanup). For log and image fixtures:

```php
use KonradMichalik\Ttt\Fixture\{ImageFixtures, LogFixtures};

$png = ImageFixtures::createPng(64, 64);                      // env-indicator, letter-avatar
LogFixtures::write($logPath, ['[2026-07-14] ERROR foo']);     // ai-mate LogsCommand
```

---

### 2.11 Arbitrary $GLOBALS entries

`WithTypo3ConfVars` only covers `$GLOBALS['TYPO3_CONF_VARS']`. For other globals (e.g. `$GLOBALS['TYPO3_REQUEST']`), which otherwise still need manual `unset()`/assignment:

**Before:**

```php
protected function setUp(): void
{
    $this->previousRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
    $GLOBALS['TYPO3_REQUEST'] = $request;
}

protected function tearDown(): void
{
    $GLOBALS['TYPO3_REQUEST'] = $this->previousRequest;
}
```

**After:**

```php
use KonradMichalik\Ttt\Attribute\WithGlobal;

#[Test]
#[WithGlobal('TYPO3_REQUEST', $request)]
public function resolvesFromCurrentRequest(): void {}
```

Restores the previous value exactly, including a previously unset key. For `$GLOBALS['TYPO3_CONF_VARS']` specifically, prefer `WithTypo3ConfVars`, which deep-merges instead of overwriting.

---

## 3. Recommended order per extension

| Extension | Migrate first | Attributes/kits |
|---|---|---|
| **letter-avatar** (Pilot 1) | 5× `Environment::initialize`, 67 ConfVars spots | `WithEnvironment`, `WithTypo3ConfVars`, `ImageFixtures` |
| **request-profiler** (Pilot 2) | `DevelopmentContextTrait`, 12 singleton pairs | `InApplicationContext`, `WithSingleton`, `JsonAssertions` |
| environment-indicator | Modifier validation (58 methods), BE_USER stubs | `ConfigurationValidationContract`, `WithBackendUser`, `WithTypo3ConfVars` |
| ai-mate | Temp-path trait, MCP asserts, log fixtures | `WithEnvironment`, `JsonAssertions`, `LogFixtures` |
| routing | Request construction (54×) | `Requests`, `JsonAssertions` |
| dump-server | ConfVars, EnvVar flags | `WithTypo3ConfVars`, `WithEnvVar` |
| file-sync | BE_USER stubs | `WithBackendUser` |
| solr-dashboard-widgets | JSON fixtures | `JsonAssertions` |

Together the two pilots cover all attribute types — only roll out broadly after they pass green (incl. a coverage diff against the phpcov baseline).

---

## 4. Gotchas & limits

- **Restore guarantee:** driven by PHPUnit's `Test\Finished` event, which fires for every test — including failures and errors. Custom tearDowns for Terrarium-managed state are unnecessary and should be deleted when migrating (a double restore is harmless, but dead code).
- **Order:** class attributes are applied before method attributes, restore runs LIFO. `#[WithEnvironment]` on the class + `#[InApplicationContext]` on the method is therefore the correct combination.
- **Environment limitation:** if the Environment was *not* initialized before the test, it stays initialized afterwards (typed static properties cannot be de-initialized). In suites that rely entirely on `#[WithEnvironment]` this is irrelevant; mixed suites should not contain tests that depend on an *un*initialized Environment.
- **getenv caveat:** `#[WithEnvVar]` affects per-request evaluations. `getenv()` calls evaluated at cache-build time (keyword `ext_localconf.php`) are not reached — known behavior from request-profiler.
- **Unsetting an env var:** `#[WithEnvVar('NAME', null)]` (or the value argument omitted) unsets the variable across `putenv()`, `$_ENV` and `$_SERVER` instead of setting it, and restores whatever value (or absence) existed before — use it to test behavior when a variable is *not* present.
- **Functional tests:** leave untouched. Terrarium is the unit sandbox; DB fixtures, extension loading and `FunctionalTestCase` remain the domain of typo3/testing-framework.
- **`#[WithTypo3ConfVars]` does not survive `FunctionalTestCase`:** the attribute applies via PHPUnit's `PreparationStarted` event, which fires *before* `setUp()`. `FunctionalTestCase::setUp()` reloads `$GLOBALS['TYPO3_CONF_VARS']` from the bootstrapped configuration afterwards, silently discarding whatever the attribute merged in. This is not a bug to fix — it follows directly from the "functional tests: leave untouched" design above. Use the imperative `ConfVarsSandbox` trait (called from `setUp()`/the test body, which runs *after* the framework's own bootstrap) instead.
- **Attribute instances:** `new` in attribute arguments (for `WithSingleton`) requires PHP ≥ 8.1 — given everywhere in the portfolio.
- **Mid-test changes:** attributes take effect before `setUp()`. If you need to change state *during* the test, use the traits (`ConfVarsSandbox`, `ApplicationContextSwitcher`) instead of the attributes.
- **`Requests`/`RequestBuilder` without an initialized Environment:** `normalizedParams` is derived via `NormalizedParams::createFromRequest()`, which requires TYPO3's `Environment` to be initialized (e.g. via `#[WithEnvironment]`). If it is not, the attribute is silently omitted rather than throwing — equivalent to calling `->withoutNormalizedParams()` explicitly.

---

## 5. Definition of Done per repo migration

1. phpcov baseline created before the migration.
2. All hand-written setUp/tearDown blocks for Terrarium-managed state removed.
3. Test suite green on the full matrix (PHP × PHPUnit × TYPO3 13/14).
4. Coverage diff against baseline: no losses.
5. Repo-local helper traits deleted where replaced by Terrarium (`WithTemporaryVarPath`, `DevelopmentContextTrait`, `CreatesTestImageTrait`, …).

---

## 6. Security & performance notes (review result)

**Security hardening in the package (v0.1.x):**

- The cleanup of temporary project directories follows **no symlinks** — links are removed instead of recursing into their target. A test that (even accidentally) creates a symlink into the repo therefore cannot cause data loss outside the sandbox.
- Temporary paths use `random_bytes` instead of predictable `uniqid`; if the computed path unexpectedly already exists (pre-creation/symlink race in shared `/tmp` environments), the handler aborts with an exception instead of adopting the foreign path.
- Directories are created with `0700` instead of `0777`; a failed `mkdir` throws instead of silently continuing.
- `#[WithEnvVar]` validates the variable name (`[A-Za-z_][A-Za-z0-9_]*`) — no `=`-injection vector via `putenv()`.

**Deliberately accepted limits (dev-only threat model):**

- Attributes are developer code: class strings in `#[WithSingleton]` and date strings in `#[FreezeTime]` are not sandboxed further — whoever writes test code runs code anyway.
- The `\assert()` type narrowings in the handlers are belt-and-braces behind the registry's `supports()` guard; with assertions disabled, the type check of the respective TYPO3 API (e.g. `setSingletonInstance`) still applies.

**Performance:**

- The attribute resolution (reflection) runs for *every* test and is therefore memoized: per class resp. per class+method it reflects exactly once; repeat runs (DataProvider cases!) only hit the cache. Attribute instances are readonly DTOs and thus safe to reuse.
- Tests without Terrarium attributes cost one cache lookup plus an empty restore call per test — in the microsecond range, no measurable suite overhead.
- The most expensive handler is `#[WithEnvironment]` (mkdir/rmdir per test). For I/O-sensitive huge classes, use it sparingly at class level; an optional class scope is planned as a later extension.

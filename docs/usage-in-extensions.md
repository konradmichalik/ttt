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

Each attribute and kit has its own "Migrating from hand-written code" section with the concrete before/after recipe:

- [`#[WithTypo3ConfVars]`](attributes/with-typo3-conf-vars.md#migrating-from-hand-written-code): the most common pattern across the portfolio (233 occurrences), incl. the mid-test `ConfVarsSandbox` variant
- [`#[WithEnvVar]`](attributes/with-env-var.md#migrating-from-hand-written-code): the `EnvVarSandbox` variant for functional test suites
- [`#[WithEnvironment]`](attributes/with-environment.md#migrating-from-hand-written-code): the block duplicated 12× (letter-avatar, ai-mate, request-profiler, routing)
- [`#[InApplicationContext]`](attributes/in-application-context.md#migrating-from-hand-written-code): replacing `DevelopmentContextTrait` (request-profiler)
- [`#[WithSingleton]`](attributes/with-singleton.md#migrating-from-hand-written-code): replacing `setSingletonInstance`/`purgeInstances` (request-profiler and others)
- [`#[WithBackendUser]`](attributes/with-backend-user.md#migrating-from-hand-written-code): replacing `BE_USER` mocks (environment-indicator, file-sync)
- [`#[FreezeTime]`](attributes/freeze-time.md#migrating-from-hand-written-code): newly possible, no prior hand-written equivalent
- [`#[WithGlobal]`](attributes/with-global.md#migrating-from-hand-written-code): arbitrary `$GLOBALS` entries beyond `TYPO3_CONF_VARS`
- [Request kit](kits/request.md#migrating-from-hand-written-code): replacing hand-built `ServerRequest`s (routing: 54×, request-profiler: 20×), incl. site settings
- [Assertion kit](kits/assertion.md#migrating-from-hand-written-code): replacing manual `json_decode()`/`assertSame()` (ai-mate MCP, routing OpenAPI, request-profiler artifacts)
- [Contract kit](kits/contract.md#migrating-from-hand-written-code): replacing hand-written validation test series (environment-indicator: 58 modifier methods)
- [Fixture kit](kits/fixture.md#migrating-from-hand-written-code): replacing `sys_get_temp_dir()` handling (ai-mate: 17×)

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
- **`#[WithTypo3ConfVars]` on `FunctionalTestCase`:** the attribute applies via PHPUnit's `Test\Prepared` event, which fires *after* `setUp()` (and any `#[Before]`/`#[PreCondition]` hooks) and immediately before the test method body. This means it now survives `FunctionalTestCase::setUp()` reloading `$GLOBALS['TYPO3_CONF_VARS']` from the bootstrapped configuration — the attribute's merge is applied on top, after that reload. Attributes that are structurally incompatible with `FunctionalTestCase` (`#[WithEnvironment]`, `#[InApplicationContext]`) still fail loudly instead. If you need state visible *inside* `setUp()` itself, use the imperative `ConfVarsSandbox` trait instead.
- **Attribute instances:** `new` in attribute arguments (for `WithSingleton`) requires PHP ≥ 8.1 — given everywhere in the portfolio.
- **Mid-test changes:** attributes take effect after `setUp()` (and before the test body), not during it. If you need to change state *during* the test — including from within `setUp()` itself — use the traits (`ConfVarsSandbox`, `ApplicationContextSwitcher`) instead of the attributes.
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

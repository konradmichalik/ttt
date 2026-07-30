# Documentation index

## Concepts

- [Lifecycle](lifecycle.md): how attribute application and restoration are wired into PHPUnit's event sequence
- [Why an extension instead of `tearDown()`?](why-extension.md): why restoration is driven by PHPUnit's event system instead of hand-written cleanup
- [Why not `#[BackupGlobals]`?](why-not-backupglobals.md): how Terrarium's per-attribute sandboxing differs from PHPUnit's built-in globals backup
- [Without the extension](without-extension.md): imperative traits for mid-test state changes

## Attributes

- [`#[WithTypo3ConfVars]`](attributes/with-typo3-conf-vars.md): deep-merges configuration into `$GLOBALS['TYPO3_CONF_VARS']`
- [`#[WithTca]`](attributes/with-tca.md): deep-merges configuration into `$GLOBALS['TCA'][$table]`
- [`#[WithGlobal]`](attributes/with-global.md): sets an arbitrary `$GLOBALS` entry
- [`#[WithEnvVar]`](attributes/with-env-var.md): sets an environment variable across `putenv()`, `$_ENV` and `$_SERVER`
- [`#[WithEnvironment]`](attributes/with-environment.md): bootstraps TYPO3's `Environment` for a single test
- [`#[InApplicationContext]`](attributes/in-application-context.md): switches the TYPO3 application context for one test
- [`#[WithSingleton]`](attributes/with-singleton.md): registers a singleton via `GeneralUtility`
- [`#[WithBackendUser]`](attributes/with-backend-user.md): provides a lightweight `$GLOBALS['BE_USER']` stub
- [`#[WithFrontendUser]`](attributes/with-frontend-user.md): provides a lightweight `$GLOBALS['FE_USER']` stub
- [`#[FreezeTime]`](attributes/freeze-time.md): pins the `Context` date aspect and the legacy execution time globals
- [`#[InTimeZone]`](attributes/in-time-zone.md): sets the default timezone for the duration of a test
- [`#[InLocale]`](attributes/in-locale.md): sets the locale for a given category
- [`#[WithStaticProperty]`](attributes/with-static-property.md): generic escape hatch for sandboxing any static property via reflection
- [`#[WithInstance]`](attributes/with-instance.md): queues a fake for the next `GeneralUtility::makeInstance()` call

## Kits

- [Request kit](kits/request.md): fluent builder for TYPO3 `ServerRequest` objects
- [Assertion kit](kits/assertion.md): dot-path based JSON assertions
- [Contract kit](kits/contract.md): generates violation-case tests from a `validateConfiguration()`-style contract
- [Fixture kit](kits/fixture.md): disposable test fixtures (images, log files)

## Guides

- [Using ttt in your own TYPO3 extensions](usage-in-extensions.md): migration guide, rollout order and gotchas for adopting Terrarium in existing test suites
- [Non-goals](non-goals.md): what's deliberately out of scope, and why

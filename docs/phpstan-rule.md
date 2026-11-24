# PHPStan rule: direct global state in tests

*ttt* ships a PHPStan rule (identifier `ttt.directGlobalState`) that reports direct changes of global state inside PHPUnit test cases and points to the attribute that does the same with guaranteed cleanup.

## Setup

Install `phpstan/extension-installer` next to *ttt*. The rule is registered through `extra.phpstan.includes` and is active only where *ttt* is installed. Without the extension installer, include `vendor/konradmichalik/ttt/extension.neon` in your `phpstan.neon`.

## What is reported

Inside classes extending `PHPUnit\Framework\TestCase`:

| Direct change | Use instead |
|---|---|
| `$GLOBALS['TYPO3_CONF_VARS'][...] = ...` | [`#[WithTypo3ConfVars]`](attributes/with-typo3-conf-vars.md) |
| `$GLOBALS['BE_USER'] = ...` | [`#[WithBackendUser]`](attributes/with-backend-user.md) |
| `$GLOBALS['TCA'][...] = ...` | [`#[WithTca]`](attributes/with-tca.md) |
| any other `$GLOBALS[...] = ...` | [`#[WithGlobal]`](attributes/with-global.md) |
| `putenv()` | [`#[WithEnvVar]`](attributes/with-env-var.md) |
| `GeneralUtility::setSingletonInstance()` | [`#[WithSingleton]`](attributes/with-singleton.md) |
| `GeneralUtility::addInstance()` | [`#[WithInstance]`](attributes/with-instance.md) |

The rule works on the syntax tree, so assignments inside heredoc fixtures are not affected.

## Scope

- Unit tests only. Classes extending the testing framework's `FunctionalTestCase` are skipped, because functional tests run with the testing framework's own bootstrap and reset behaviour.
- The tests of *ttt* itself (namespace `KonradMichalik\Ttt\Tests`) are excluded.

## Ignoring a finding

Where a direct change is intended, for example because the state has to change in the middle of a test, ignore the identifier on that line:

```php
// @phpstan-ignore ttt.directGlobalState
$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = 'changed mid-test';
```

Or for the whole project in `phpstan.neon`:

```neon
parameters:
    ignoreErrors:
        - identifier: ttt.directGlobalState
```

For mid-test changes the imperative [traits](without-extension.md) restore the state as well.

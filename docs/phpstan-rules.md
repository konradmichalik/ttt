# PHPStan rules

ttt ships PHPStan rules that point to the attribute replacing hand-written test state. They are part of this package, so there is nothing extra to install besides PHPStan 2.1 or newer.

## Setup

With [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer) the [rules](../rules.neon) are included automatically. Without it, include them by hand:

```yaml
# phpstan.neon

includes:
  - %rootDir%/../../konradmichalik/ttt/rules.neon

parameters:
  paths:
    - Tests/Unit
```

## Rules

| Identifier | Reported when | Use instead |
|------------|---------------|-------------|
| `ttt.manualGlobals` | `$GLOBALS['TYPO3_CONF_VARS']` is assigned below its root | [`#[WithTypo3ConfVars]`](attributes/with-typo3-conf-vars.md) |
| `ttt.manualGlobals` | `$GLOBALS['TCA']` is assigned below its root | [`#[WithTca]`](attributes/with-tca.md) |
| `ttt.manualEnvVar` | `putenv()` is called, to set or to clear a variable | [`#[WithEnvVar]`](attributes/with-env-var.md) |

```php
// Reported: ttt.manualEnvVar
putenv('MY_EXT_FEATURE=1');

// Instead
#[WithEnvVar('MY_EXT_FEATURE', '1')]
public function resolvesConfiguration(): void {}
```

Only classes that extend PHPUnit's `TestCase` are checked. `FunctionalTestCase` is skipped, because ttt rejects some attributes there.

A few cases are deliberately not reported:

- A test class, one of its parent classes or the test method that already carries the matching attribute. ttt restores the whole global afterwards, so more state inside that test is safe.
- Replacing a whole global, as in `$GLOBALS['TYPO3_CONF_VARS'] = [...]`. The attributes deep-merge, so they would change what such a test does.
- Compound assignments (`??=`, `.=`) and `unset()`. Only plain `=` is checked.
- `$GLOBALS['BE_USER']`. Tests usually assign a mock with expectations there, which `#[WithBackendUser]` cannot replace.

## Typical overrides

A test that needs an invalid value, which a deep merge cannot express, keeps its manual assignment. Ignore the rule inline and keep the reason next to it:

```php
// @phpstan-ignore ttt.manualGlobals (tests an invalid, non-array configuration)
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['my_ext'] = 'not-an-array';
```

Or ignore one file by identifier and path:

```yaml
parameters:
  ignoreErrors:
    -
      identifier: ttt.manualEnvVar
      path: Tests/Unit/Utility/EnvironmentHelperTest.php
```

# [`#[WithGlobal]`](../../src/Attribute/WithGlobal.php)

_Scope: Class & Method level · Repeatable_

Sets an arbitrary `$GLOBALS` entry before the test body runs (after `setUp()`) and restores the previous value (including a previously unset key) afterwards. For `$GLOBALS['TYPO3_CONF_VARS']` specifically, prefer [`#[WithTypo3ConfVars]`](with-typo3-conf-vars.md), which deep-merges instead of overwriting.

## Example

```php
#[WithGlobal('MY_APP_SETTINGS', ['mode' => 'strict'])]
public function behavesInStrictMode(): void {}
```

Attribute arguments must be constant expressions: scalars, arrays and `new` expressions such as `new ServerRequest('https://example.com/')` work, a prepared variable or a mock does not.

<details>
<summary>More examples</summary>

### Repeatable across multiple keys

```php
#[WithGlobal('TYPO3_REQUEST', new ServerRequest('https://example.com/'))]
#[WithGlobal('MY_APP_SETTINGS', ['mode' => 'strict'])]
public function resolvesFromCurrentRequestInStrictMode(): void {}
```

### Restoring a previously unset key

```php
#[WithGlobal('MY_APP_FLAG', true)]
public function behavesAsIfFlagWasSet(): void
{
    // $GLOBALS['MY_APP_FLAG'] existed only for this test; afterwards it's unset again, exactly as before.
}
```

</details>

## Migrating from hand-written code

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
#[WithGlobal('TYPO3_REQUEST', new ServerRequest('https://example.com/'))]
public function resolvesFromCurrentRequest(): void {}
```

Restores the previous value exactly, including a previously unset key.

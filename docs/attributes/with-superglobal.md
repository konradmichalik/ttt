# [`#[WithSuperglobal]`](../../src/Attribute/WithSuperglobal.php)

_Scope: Class & Method level · Repeatable_

Sets a single key of a PHP superglobal (`$_SERVER`, `$_GET`, `$_POST` or `$_ENV`) before the test is prepared and restores the previous value (including a previously unset key) afterwards. For actual environment variables, prefer [`#[WithEnvVar]`](with-env-var.md), which also drives `putenv()`.

## Example

```php
#[WithSuperglobal('_SERVER', 'REMOTE_ADDR', '203.0.113.1')]
public function resolvesClientIp(): void {}
```

<details>
<summary>More examples</summary>

### Repeatable across multiple keys

```php
#[WithSuperglobal('_SERVER', 'REMOTE_ADDR', '203.0.113.1')]
#[WithSuperglobal('_SERVER', 'HTTP_X_FORWARDED_FOR', '198.51.100.1')]
public function resolvesForwardedClientIp(): void {}
```

### Unsetting a previously set key

Passing `null` as the value unsets the key instead of setting it, e.g. to test behaviour when a header is absent:

```php
#[WithSuperglobal('_SERVER', 'HTTP_X_FORWARDED_FOR', null)]
public function fallsBackToRemoteAddrWithoutForwardedHeader(): void {}
```

</details>

## Migrating from hand-written code

**Before:**

```php
protected function setUp(): void
{
    $this->previousRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
    $_SERVER['REMOTE_ADDR'] = '203.0.113.1';
}

protected function tearDown(): void
{
    if (null === $this->previousRemoteAddr) {
        unset($_SERVER['REMOTE_ADDR']);
    } else {
        $_SERVER['REMOTE_ADDR'] = $this->previousRemoteAddr;
    }
}
```

**After:**

```php
use KonradMichalik\Ttt\Attribute\WithSuperglobal;

#[Test]
#[WithSuperglobal('_SERVER', 'REMOTE_ADDR', '203.0.113.1')]
public function resolvesClientIp(): void {}
```

Restores the previous value exactly, including a previously unset key.

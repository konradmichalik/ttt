# [`#[WithTypo3ConfVars]`](../../src/Attribute/WithTypo3ConfVars.php)

_Scope: Class & Method level · Repeatable_

Deep-merges the given configuration into `$GLOBALS['TYPO3_CONF_VARS']` before the test is prepared and guarantees a full restore afterwards, regardless of the test outcome. Class-level attributes are applied before method-level attributes; later attributes win on conflicting keys.

## Example

```php
#[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => []]]])]
public function resolvesConfiguration(): void {}
```

<details>
<summary>More examples</summary>

### Class level

```php
#[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => []]]])]
final class HandlerTest extends TestCase
{
    #[Test]
    public function resolvesConfiguration(): void
    {
        // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['my_ext']['configuration'] is set for every test in this class.
    }
}
```

### Method level overrides class level

```php
#[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => ['a' => 1]]]])]
final class HandlerTest extends TestCase
{
    #[Test]
    #[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => ['a' => 2]]]])]
    public function methodLevelWins(): void
    {
        // 'a' is 2 here: method-level attributes merge on top of class-level ones.
    }
}
```

### Clearing a key with the sentinel

```php
use KonradMichalik\Ttt\Attribute\Typo3ConfVarsSentinel;

#[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => Typo3ConfVarsSentinel::Unset]]])]
public function behavesAsIfConfigurationWasNeverSet(): void {}
```

</details>

## Migrating from hand-written code

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

The class attribute applies to all tests of the class, method attributes merge on top (the method wins on conflicts). The merge is a deep merge, so it is enough to specify the subtree you actually need. Restore is guaranteed to run, even when the test fails hard.

For manipulations **mid-test** (e.g. changing configuration and resolving it again), use the imperative [`ConfVarsSandbox`](../../src/Traits/ConfVarsSandbox.php) trait instead:

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

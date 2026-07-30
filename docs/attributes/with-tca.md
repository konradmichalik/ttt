# [`#[WithTca]`](../../src/Attribute/WithTca.php)

_Scope: Class & Method level · Repeatable_

Deep-merges the given configuration into `$GLOBALS['TCA'][$table]` before the test is prepared and guarantees a full restore afterwards, regardless of the test outcome. Class-level attributes are applied before method-level attributes; later attributes win on conflicting keys. Same merge and sentinel semantics as [`#[WithTypo3ConfVars]`](with-typo3-conf-vars.md): use `Typo3ConfVarsSentinel::Unset` to explicitly clear a key that would otherwise survive the merge.

## Example

```php
#[WithTca('tt_content', ['columns' => ['tx_myext_field' => ['config' => ['type' => 'input']]]])]
#[WithTca('pages', ['ctrl' => ['label' => 'nav_title']])]
public function resolvesConfiguration(): void {}
```

<details>
<summary>More examples</summary>

### Repeatable across multiple tables

```php
final class HandlerTest extends TestCase
{
    #[Test]
    #[WithTca('tt_content', ['columns' => ['tx_myext_field' => ['config' => ['type' => 'input']]]])]
    #[WithTca('pages', ['ctrl' => ['label' => 'nav_title']])]
    public function resolvesConfigurationAcrossTables(): void
    {
        // Both $GLOBALS['TCA']['tt_content'] and $GLOBALS['TCA']['pages'] are merged, both fully restored.
    }
}
```

### Clearing a key with the sentinel

```php
use KonradMichalik\Ttt\Attribute\Typo3ConfVarsSentinel;

#[WithTca('tt_content', ['ctrl' => ['hideAtCopy' => Typo3ConfVarsSentinel::Unset]])]
public function behavesAsIfHideAtCopyWasNeverSet(): void {}
```

</details>

# [`#[InLocale]`](../../src/Attribute/InLocale.php)

_Scope: Class & Method level · Repeatable_

Sets the locale (`setlocale()`) for the given category for the duration of the test and restores the previous one afterwards. Port of JUnit Pioneer's `@DefaultLocale`. No `typo3/cms-core` requirement. Repeatable, so independent categories (`LC_TIME`, `LC_MONETARY`, ...) can be set separately.

The locale is process-global state: safe under `paratest` (one process per worker), unsafe under any runner sharing a process across tests running concurrently.

## Example

```php
#[InLocale(LC_ALL, 'de_DE.UTF-8')]
public function formatsNumbersInGermanNotation(): void {}
```

<details>
<summary>More examples</summary>

### Independent categories, repeated

```php
#[InLocale(LC_TIME, 'de_DE.UTF-8')]
#[InLocale(LC_MONETARY, 'en_US.UTF-8')]
public function formatsTimeInGermanAndCurrencyInUsNotation(): void {}
```

### Class level

```php
#[InLocale(LC_ALL, 'de_DE.UTF-8')]
final class NumberFormatterTest extends TestCase
{
    #[Test]
    public function formatsNumbersInGermanNotation(): void {}
}
```

</details>

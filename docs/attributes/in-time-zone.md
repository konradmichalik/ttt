# [`#[InTimeZone]`](../../src/Attribute/InTimeZone.php)

_Scope: Class & Method level_

Sets the default timezone (`date_default_timezone_set()`) for the duration of the test and restores the previous one afterwards. Port of JUnit Pioneer's `@DefaultTimeZone`. No `typo3/cms-core` requirement.

The default timezone is process-global state: safe under `paratest` (one process per worker), unsafe under any runner sharing a process across tests running concurrently.

## Example

```php
#[InTimeZone('Europe/Berlin')]
public function formatsDatesInTheConfiguredTimeZone(): void {}
```

<details>
<summary>More examples</summary>

### Class level

```php
#[InTimeZone('America/New_York')]
final class DateFormatterTest extends TestCase
{
    #[Test]
    public function formatsDatesInTheConfiguredTimeZone(): void {}
}
```

### Method level overrides class level

```php
#[InTimeZone('Europe/Berlin')]
final class DateFormatterTest extends TestCase
{
    #[Test]
    #[InTimeZone('Asia/Tokyo')]
    public function usesTokyoTimeForThisTestOnly(): void {}
}
```

</details>

# [`#[FreezeTime]`](../../src/Attribute/FreezeTime.php)

_Scope: Class & Method level_

Pins TYPO3's date aspect (`Context` `"date"`) and the legacy execution time globals (`EXEC_TIME`, `SIM_EXEC_TIME`, `ACCESS_TIME`, `SIM_ACCESS_TIME`) to a fixed point in time for deterministic tests. Accepts any string understood by `DateTimeImmutable::__construct()`.

Scope is deliberately narrow: it does **not** affect `new DateTimeImmutable()`, `time()` or `date()` calls in the code under test, since those read the system clock directly rather than TYPO3's time abstractions.

Requires `typo3/cms-core`.

## Example

```php
#[FreezeTime('2026-07-14T12:00:00Z')]
public function calculatesRelativeToFixedTime(): void {}
```

<details>
<summary>More examples</summary>

### Class level

```php
#[FreezeTime('2026-07-14T12:00:00Z')]
final class ExpiryCalculatorTest extends TestCase
{
    #[Test]
    public function calculatesRelativeToFixedTime(): void {}

    #[Test]
    public function flagsExpiredRecordsRelativeToFixedTime(): void {}
}
```

### Relative date strings

```php
#[FreezeTime('2026-01-01T00:00:00Z')]
#[FreezeTime('+1 day')]
public function combinesWithRelativeDateStrings(): void {}
```

</details>

## Migrating from hand-written code

**After (newly possible):**

```php
#[Test]
#[FreezeTime('2026-07-14T12:00:00Z')]
public function calculatesExpiryFromNow(): void
{
    // The Context 'date' aspect AND $GLOBALS['EXEC_TIME']/ACCESS_TIME are pinned
}
```

Covers both time sources that TYPO3 code typically uses. Code with raw `time()`/`new \DateTime()` stays unaffected; that code is the actual refactoring candidate (inject the `Context` aspect).

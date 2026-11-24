# [`#[WithRecords]`](../../src/Attribute/WithRecords.php)

_Scope: Class & Method level · Repeatable_

Creates records through a [record factory](../kits/factory.md) before the test body runs and deletes every row it inserted afterwards: records, MM rows and translations, regardless of the test outcome.

Requires `typo3/cms-core` and `fakerphp/faker`, and a database: use it in functional tests. The attribute is applied after `setUp()`, when the database of a `FunctionalTestCase` is initialized.

## Example

```php
#[Test]
#[WithRecords(NewsFactory::class, count: 3, states: ['published'], overrides: ['title' => 'Fixed title'])]
public function listShowsPublishedNews(): void
{
    $news = Records::of(NewsFactory::class);
    // ...
}
```

<details>
<summary>More examples</summary>

### Several factories

```php
#[WithRecords(PageFactory::class)]
#[WithRecords(NewsFactory::class, count: 5)]
public function listsNewsOnThePage(): void {}
```

Records of the same factory from several attributes are merged by `Records::of()`.

### Class level

```php
#[WithRecords(PageFactory::class)]
final class NewsListTest extends FunctionalTestCase {}
```

The records are created and removed for every test of the class.

### Relations

Attribute arguments are constant expressions, so relations stay in the imperative API. Create such records in the test with `NewsFactory::new()->has(...)->create()`.

</details>

## Arguments

| Argument | Meaning |
|---|---|
| `factory` | A class extending `RecordFactory` |
| `count` | Number of records, default `1` |
| `states` | Names of state methods on the factory, applied in order |
| `overrides` | Scalar values applied last, they win over defaults and states |

An unknown state method or a class that is no factory throws. `Records::of()` throws when no record of that factory was created for the running test.

Cleanup runs after `tearDown()`, together with the restore of every other attribute, so the database connection has to still be open then.

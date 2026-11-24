# [Factory kit](../../src/Factory/RecordFactory.php)

Record factories for functional tests. They replace CSV fixtures and hand-written `insert()` calls: defaults for every column come from the TCA, so a test only spells out what it cares about, and relations and translations are wired from the TCA instead of magic UIDs.

Requires `typo3/cms-core` and `fakerphp/faker`, both are in `suggest`. Factories write to the real test database, use them in functional tests. Without a database connection they fail with an actionable message.

## Example

```php
use Faker\Generator;
use KonradMichalik\Ttt\Factory\RecordFactory;

final class NewsFactory extends RecordFactory
{
    protected string $table = 'tx_news_domain_model_news';

    protected function definition(Generator $faker): array
    {
        return ['title' => $faker->sentence()];
    }

    public function published(): static
    {
        return $this->state(['hidden' => 0, 'starttime' => 0, 'endtime' => 0]);
    }
}

$news = NewsFactory::new()
    ->published()
    ->count(3)
    ->for(PageFactory::new(), 'pid')
    ->has(CategoryFactory::new()->count(2), 'categories')
    ->create();

$news->first()->getUid();
```

`definition()` is optional. A factory without it creates a valid record from the TCA alone.

<details>
<summary>More examples</summary>

### Defaults derived from the TCA

Every column without a value gets one: `input` and `text` get words, `datetime` a date (honouring `dbType`), `check` its default or `0`, `select` and `radio` their first static item, `number` a value inside its `range`. A configured `default` always wins. Relations stay empty unless set explicitly. Content Blocks are covered, they generate regular TCA. `crdate` and `tstamp` named in `ctrl` are filled.

The values come from a Faker seeded with the running test, so the same test produces the same data on every run.

### Rows without persisting

```php
$rows = NewsFactory::new()->count(2)->make(); // list of arrays, relations not resolved
```

### Relations

`for(Factory, field)` creates the parent first and stores its uid in the field. `has(Factory, field)` creates the children afterwards and wires them according to the TCA of the field:

| TCA of the field | Result |
|---|---|
| `inline` with `foreign_field` | children get the parent uid (and `foreign_table_field`, `foreign_sortby` if configured), the field holds the count |
| `MM` (select, group, inline) | rows in the MM table, `MM_match_fields` are written, the field holds the count |
| `category` | rows in the MM table including `tablenames` and `fieldname` |
| `select` or `group` without `MM` | comma separated list of uids |

A child factory whose table does not match the `foreign_table` of the field is rejected.

### Translations

```php
$news = NewsFactory::new()->translated(1, ['title' => 'Übersetzt'])->create()->first();

$news->translations[0]->get('title');
```

The overlay record gets the language and the parent pointer from the `languageField` and `transOrigPointerField` of the `ctrl` section. A table without those fields cannot be translated.

### Cleanup

Records created with [`#[WithRecords]`](../attributes/with-records.md) are deleted again after the test, including MM rows and translations. Records created by calling `create()` yourself stay in the database, the testing framework resets it anyway. To remove them early, call `$collection->log()->deleteAll()`.

</details>

## Not included

- Persisting through the `DataHandler`: it needs a backend user and is much slower, hooks, slugs and the reference index are not reproduced
- `withFile()` creating `sys_file` and `sys_file_reference`
- A scaffolding command for factories
- Workspace versions

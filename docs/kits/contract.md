# [Contract kit](../../src/Contract/ConfigurationValidationContract.php)

[`ConfigurationValidationContract`](../../src/Contract/ConfigurationValidationContract.php) describes a `validateConfiguration()`-style API once: the contract generates the violation cases (missing required key, wrong type, out-of-range value) as separate test cases.

Schema DSL per key: `string` | `int` | `float` | `bool` | `array` | `enum`, numeric types optionally with `:min..max` (e.g. `float:0..1`), `enum` with `:a|b|c` (e.g. `enum:top left|top right`), which additionally generates an "unrecognized value" violation. Optional keys carry a `?` suffix on the key name (e.g. `position?`).

## Example

```php
final class CircleModifierValidationTest extends ConfigurationValidationContract
{
    protected function isValid(array $configuration): bool
    {
        return (new CircleModifier())->validateConfiguration($configuration);
    }

    protected function validConfiguration(): array
    {
        return ['color' => '#ff0000', 'size' => 0.5];
    }

    protected function schema(): array
    {
        return ['color' => 'string', 'size' => 'float:0..1', 'position?' => 'string'];
    }
}
```

<details>
<summary>More examples</summary>

### Enum values

```php
protected function schema(): array
{
    return ['position' => 'enum:top left|top right|bottom left|bottom right'];
}
```

Generates a wrong-type violation and, additionally, an "unrecognized value" violation for a `string` that isn't one of the listed options.

### Optional keys

```php
protected function validConfiguration(): array
{
    return ['color' => '#ff0000']; // 'size' omitted entirely
}

protected function schema(): array
{
    return ['color' => 'string', 'size?' => 'float:0..1'];
}
```

An optional key (`?` suffix) does not generate a "missing required key" violation, but still generates a wrong-type violation if present with the wrong type.

</details>

## Migrating from hand-written code

Instead of a hand-written `testValidateConfigurationFailsWithMissingColor()` series:

```php
use KonradMichalik\Ttt\Contract\ConfigurationValidationContract;

final class CircleModifierValidationTest extends ConfigurationValidationContract
{
    protected function isValid(array $configuration): bool
    {
        return (new CircleModifier())->validateConfiguration($configuration);
    }

    protected function validConfiguration(): array
    {
        return ['color' => '#ff0000', 'size' => 0.5];
    }

    protected function schema(): array
    {
        return [
            'color' => 'string',
            'size' => 'float:0..1',
            'position?' => 'enum:top left|top right|bottom left|bottom right',
        ];
    }
}
```

Automatically generated: missing required key, wrong type per key, under-/overshoot per range, and (for `enum`) an unrecognized-value case, i.e. a known-but-invalid string like `"top center"` for a `position` key restricted to a fixed set. Add extension-specific special cases (e.g. the hex format of `color`) as additional plain `#[Test]` methods in the same class: the contract is the baseline, not the ceiling.

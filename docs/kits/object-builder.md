# Object builder

[`ObjectBuilder`](../../src/Reflection/ObjectBuilder.php) constructs an instance without calling its constructor and injects property values via reflection, replacing hand-written `(new ReflectionClass($class))->newInstanceWithoutConstructor()` plus a per-property `ReflectionProperty::setValue()` loop. Works for private, protected and (uninitialized) readonly typed properties alike.

Not a sandbox attribute: there is no global state to restore here, just object construction, so it sits outside the attribute mechanism the same way the [Request kit](request.md) does.

## Example

```php
$instance = ObjectBuilder::withoutConstructor(StorageController::class)
    ->withProperty('connectionPool', $connectionPoolStub)
    ->build();
```

## Migrating from hand-written code

**Before:**

```php
$instance = (new ReflectionClass(StorageController::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(StorageController::class, 'connectionPool'))->setValue($instance, $connectionPoolStub);
```

**After:**

```php
use KonradMichalik\Ttt\Reflection\ObjectBuilder;

$instance = ObjectBuilder::withoutConstructor(StorageController::class)
    ->withProperty('connectionPool', $connectionPoolStub)
    ->build();
```

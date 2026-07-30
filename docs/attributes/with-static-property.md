# [`#[WithStaticProperty]`](../../src/Attribute/WithStaticProperty.php)

_Scope: Class & Method level · Repeatable_

Generic escape hatch for sandboxing an arbitrary static property via reflection, equivalent to pytest's `monkeypatch.setattr` for the long tail of static state that doesn't have a dedicated attribute yet. Prefer a dedicated attribute where one exists; reach for this only when none does.

Works for private/protected statics via `ReflectionProperty`. Two cases fail loudly instead of silently misbehaving:

- a readonly static property cannot be sandboxed (PHP does not allow assigning to it a second time);
- a property that is not yet initialized cannot be sandboxed either. PHP's reflection API has no way to revert a typed property back to the uninitialized state, so restoring it to `null` would silently change its behavior (a nullable property explicitly set to `null` differs from one that was never initialized: reading the latter throws).

## Example

```php
#[WithStaticProperty(GeneralUtility::class, 'indpEnvCache', [])]
```

<details>
<summary>More examples</summary>

### Repeatable across multiple properties

```php
#[WithStaticProperty(GeneralUtility::class, 'indpEnvCache', [])]
#[WithStaticProperty(MyCache::class, 'instances', [])]
public function resetsMultipleStaticCaches(): void {}
```

### Private static property

```php
#[WithStaticProperty(MyRegistry::class, 'entries', ['foo' => 'bar'])]
public function readsFromReflectionSandboxedRegistry(): void {}
```

</details>

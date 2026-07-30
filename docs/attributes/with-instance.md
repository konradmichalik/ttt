# [`#[WithInstance]`](../../src/Attribute/WithInstance.php)

_Scope: Class & Method level · Repeatable_

Queues a fake for `GeneralUtility::makeInstance($className)` via `GeneralUtility::addInstance()`: the FIFO queue for non-singleton fakes, as opposed to [`#[WithSingleton]`](with-singleton.md)'s `setSingletonInstance()`. The queued instance is consumed by the first matching `makeInstance()` call, exactly per normal `addInstance()` semantics.

Restore-only scope: any instance still queued at test end (never consumed) is purged so it cannot leak into the next test. This does **not** assert that the instance was actually consumed; that would require a post-condition hook, which this attribute does not provide.

Requires `typo3/cms-core`.

## Example

```php
#[WithInstance(MyMailer::class, new FakeMailer())]
```

<details>
<summary>More examples</summary>

### Repeatable, FIFO order

```php
#[WithInstance(MyMailer::class, new FakeMailer('first'))]
#[WithInstance(MyMailer::class, new FakeMailer('second'))]
public function consumesFakesInQueueOrder(): void
{
    // The first makeInstance(MyMailer::class) call receives 'first', the second receives 'second'.
}
```

### Unconsumed fakes are purged, not leaked

```php
#[WithInstance(MyMailer::class, new FakeMailer())]
public function doesNotLeakUnusedFakeIntoNextTest(): void
{
    // Even if makeInstance(MyMailer::class) is never called here, the queue is empty for the next test.
}
```

</details>

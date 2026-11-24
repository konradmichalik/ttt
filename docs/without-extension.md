# Without the extension

For imperative use (or mid-test changes) the same handlers are available as traits:

```php
use KonradMichalik\Ttt\Traits\ConfVarsSandbox;

final class HandlerTest extends TestCase
{
    use ConfVarsSandbox;

    #[Test]
    public function resolvesConfiguration(): void
    {
        $this->setTypo3ConfVars(['EXTCONF' => ['my_ext' => []]]);
        // ...
    }
}
```

The trait restores its state after every test through a PHPUnit `#[After]` hook, no `tearDown()` is needed.

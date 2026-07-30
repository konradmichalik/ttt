<div align="center">

# *ttt*

[![Coverage](https://img.shields.io/coverallsCoverage/github/konradmichalik/ttt?logo=coveralls)](https://coveralls.io/github/konradmichalik/ttt)
[![CGL](https://img.shields.io/github/actions/workflow/status/konradmichalik/ttt/cgl.yml?label=cgl&logo=github)](https://github.com/konradmichalik/ttt/actions/workflows/cgl.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/konradmichalik/ttt/tests.yml?label=tests&logo=github)](https://github.com/konradmichalik/ttt/actions/workflows/tests.yml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/konradmichalik/ttt/php?logo=php)](https://packagist.org/packages/konradmichalik/ttt)

</div>

*ttt* (**TYPO3 Testing Terrarium**) is a PHPUnit testing toolbox for TYPO3 extension development. It puts `TYPO3_CONF_VARS`, environment variables, application context & more in place declaratively via PHP attributes — guaranteed to be cleaned up afterwards, whether the test passes, fails or errors.

**Before:**

```php
final class HandlerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->backup = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['my_ext']['configuration'] = [];
        putenv('MY_EXT_FEATURE=1');
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->backup; // skipped on hard errors!
        putenv('MY_EXT_FEATURE');
    }

    public function testResolvesConfiguration(): void { /* ... */ }
}
```

**After:**

```php
#[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => []]]])]
final class HandlerTest extends TestCase
{
    #[Test]
    #[WithEnvVar('MY_EXT_FEATURE', '1')]
    public function resolvesConfiguration(): void { /* just Arrange–Act–Assert */ }
}
```

## 🔥 Installation

[![Packagist](https://img.shields.io/packagist/v/konradmichalik/ttt?label=version&logo=packagist)](https://packagist.org/packages/konradmichalik/ttt)
[![Packagist Downloads](https://img.shields.io/packagist/dt/konradmichalik/ttt?color=brightgreen)](https://packagist.org/packages/konradmichalik/ttt)

```bash
composer require --dev konradmichalik/ttt
```

## ⚡ Usage

Register the extension once in your `phpunit.xml`:

```xml
<extensions>
    <bootstrap class="KonradMichalik\Ttt\TttExtension"/>
</extensions>
```

That's it — all *ttt* attributes now work in every test. Attributes can be placed on classes and methods (class level is applied first, method level merges on top) and are repeatable.

- [Available attributes](#available-attributes)
- [Request kit](#request-kit)
- [Assertion kit](#assertion-kit)
- [Contract kit](#contract-kit)
- [Fixture kit](#fixture-kit)
- [Why an extension instead of tearDown()?](#why-an-extension-instead-of-teardown)
- [Without the extension](#without-the-extension)

### Available attributes

| Attribute | Purpose | Requires |
|---|---|---|
| `#[WithTypo3ConfVars([...])]` | Deep-merges configuration into `$GLOBALS['TYPO3_CONF_VARS']`, full restore afterwards | — |
| `#[WithEnvVar('NAME', 'value')]` | Sets an environment variable (`putenv()`, `$_ENV`, `$_SERVER`), restores all three channels | — |
| `#[WithEnvironment(...)]` | Bootstraps `Environment::initialize()` in a temporary project directory incl. cleanup | typo3/cms-core |
| `#[InApplicationContext('Development')]` | Switches the TYPO3 application context for one test | typo3/cms-core |
| `#[WithSingleton(Foo::class, new FakeFoo())]` | Registers a singleton via `GeneralUtility`, restores the previous singleton map | typo3/cms-core |
| `#[WithBackendUser(admin: true)]` | Provides a lightweight `$GLOBALS['BE_USER']` stub and the matching `Context` `backend.user` aspect | typo3/cms-core |
| `#[FreezeTime('2026-07-14T12:00:00Z')]` | Pins the Context date aspect and `EXEC_TIME` globals | typo3/cms-core |

### Request kit

```php
use KonradMichalik\Ttt\Http\Requests;

$request = Requests::post('/api/items')
    ->withJsonBody(['title' => 'Terrarium'])
    ->withRemoteAddress('10.0.0.1')
    ->build(); // TYPO3 ServerRequest incl. normalizedParams attribute
```

### Assertion kit

```php
use KonradMichalik\Ttt\Assertion\JsonAssertions;

final class McpResponseTest extends TestCase
{
    use JsonAssertions;

    #[Test]
    public function returnsItems(): void
    {
        self::assertJsonPath($response, 'result.items.0.uid', 42);
        self::assertJsonHasPaths($response, ['result', 'result.items']);
        self::assertJsonPathEqualsWithDelta($response, 'result.hitRatio', 0.667, 0.001);
    }
}
```

### Contract kit

Describe a `validateConfiguration()`-style API once — the contract generates the violation cases (missing required key, wrong type, out-of-range):

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

### Fixture kit

```php
$png = ImageFixtures::createPng(64, 64);          // disposable GD test image
LogFixtures::write($path, ['line one', 'line two']);
```

### Why an extension instead of tearDown()?

The restore logic is driven by PHPUnit's event system (`Test\Finished` fires for **every** test, regardless of outcome). Hand-written `tearDown()` cleanup can be skipped by hard errors and leak state into subsequent tests — Terrarium can't.

### Without the extension

For imperative use (or mid-test changes) the same handlers are available as traits:

```php
use KonradMichalik\Ttt\Traits\ConfVarsSandbox;

final class HandlerTest extends TestCase
{
    use ConfVarsSandbox;

    protected function tearDown(): void
    {
        $this->restoreTypo3ConfVars();
    }

    #[Test]
    public function resolvesConfiguration(): void
    {
        $this->setTypo3ConfVars(['EXTCONF' => ['my_ext' => []]]);
        // ...
    }
}
```

## 🧩 Extending

Custom attributes are two small classes: a DTO implementing `TttAttribute` and an `AttributeHandler` that applies the state and returns a restorer closure. Handlers must be stateless — all captured state belongs into the closure.

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).

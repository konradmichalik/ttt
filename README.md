<div align="center">

# *ttt*

[![Coverage](https://img.shields.io/coverallsCoverage/github/konradmichalik/ttt?logo=coveralls)](https://coveralls.io/github/konradmichalik/ttt)
[![CGL](https://img.shields.io/github/actions/workflow/status/konradmichalik/ttt/cgl.yml?label=cgl&logo=github)](https://github.com/konradmichalik/ttt/actions/workflows/cgl.yml)
[![Tests](https://img.shields.io/github/actions/workflow/status/konradmichalik/ttt/tests.yml?label=tests&logo=github)](https://github.com/konradmichalik/ttt/actions/workflows/tests.yml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/konradmichalik/ttt/php?logo=php)](https://packagist.org/packages/konradmichalik/ttt)

</div>

*ttt* (**TYPO3 Testing Terrarium**) is a PHPUnit testing toolbox for TYPO3 extension development. At its core is declarative test sandboxing: `TYPO3_CONF_VARS`, environment variables, application context & more, put in place via PHP attributes and guaranteed to be cleaned up afterwards, whether the test passes, fails or errors. A Request kit, an Assertion kit, a Contract kit and a Fixture kit round out the rest of everyday TYPO3 test writing.

See [Documentation index](docs/README.md "Full list of attributes, kits and concepts") for the complete list of attributes, kits and concepts.

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

See [`docs/why-not-backupglobals.md`](docs/why-not-backupglobals.md) for how this differs from PHPUnit's built-in `#[BackupGlobals]`.

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

That's it: all *ttt* attributes now work in every test. Attributes can be placed on classes and methods (class level is applied first, method level merges on top) and are repeatable.

### Available attributes

| Attribute | Purpose |
|---|---|
| [`#[WithTypo3ConfVars([...])]`](docs/attributes/with-typo3-conf-vars.md) | Deep-merges configuration into `$GLOBALS['TYPO3_CONF_VARS']`, full restore afterwards |
| [`#[WithTca('tt_content', [...])]`](docs/attributes/with-tca.md) | Deep-merges configuration into `$GLOBALS['TCA'][$table]`, full restore afterwards |
| [`#[WithGlobal('KEY', $value)]`](docs/attributes/with-global.md) | Sets an arbitrary `$GLOBALS` entry, full restore afterwards (incl. previously unset keys) |
| [`#[WithEnvVar('NAME', 'value')]`](docs/attributes/with-env-var.md) | Sets an environment variable (`putenv()`, `$_ENV`, `$_SERVER`), restores all three channels |
| [`#[WithEnvironment(...)]`](docs/attributes/with-environment.md) 🧪 | Bootstraps `Environment::initialize()` in a temporary project directory incl. cleanup |
| [`#[InApplicationContext('Development')]`](docs/attributes/in-application-context.md) 🧪 | Switches the TYPO3 application context for one test |
| [`#[WithSingleton(Foo::class, new FakeFoo())]`](docs/attributes/with-singleton.md) | Registers a singleton via `GeneralUtility`, restores the previous singleton map |
| [`#[WithBackendUser(admin: true)]`](docs/attributes/with-backend-user.md) | Provides a lightweight `$GLOBALS['BE_USER']` stub and the matching `Context` `backend.user` aspect |
| [`#[WithFrontendUser(uid: 42)]`](docs/attributes/with-frontend-user.md) | Provides a lightweight `$GLOBALS['FE_USER']` stub and the matching `Context` `frontend.user` aspect |
| [`#[FreezeTime('2026-07-14T12:00:00Z')]`](docs/attributes/freeze-time.md) | Pins the Context date aspect and `EXEC_TIME` globals |
| [`#[InTimeZone('Europe/Berlin')]`](docs/attributes/in-time-zone.md) | Sets the default timezone (`date_default_timezone_set()`) |
| [`#[InLocale(LC_ALL, 'de_DE.UTF-8')]`](docs/attributes/in-locale.md) | Sets the locale (`setlocale()`) for a given category |
| [`#[WithStaticProperty(Foo::class, 'bar', 'value')]`](docs/attributes/with-static-property.md) | Generic escape hatch: overwrites any static property via reflection, full restore afterwards |
| [`#[WithInstance(Foo::class, new FakeFoo())]`](docs/attributes/with-instance.md) | Queues a fake via `GeneralUtility::addInstance()` for the *next* `makeInstance()` call |

> [!IMPORTANT]
> 🧪 Unit tests only. Fails loudly if used on a `FunctionalTestCase` (the framework already owns `Environment` and the compiled container by the time it would apply). See each attribute's doc for requirements and further caveats.

### Kits

| Kit | Purpose |
|---|---|
| [Request kit](docs/kits/request.md) | Fluent builder for TYPO3 `ServerRequest` objects |
| [Assertion kit](docs/kits/assertion.md) | JSON path assertions with descriptive failure messages |
| [Contract kit](docs/kits/contract.md) | Generates violation-case tests from a `validateConfiguration()`-style contract |
| [Fixture kit](docs/kits/fixture.md) | Disposable test fixtures (images, log files) |

See [`docs/lifecycle.md`](docs/lifecycle.md) for how attribute application and restoration are wired into PHPUnit's event lifecycle, [`docs/why-extension.md`](docs/why-extension.md) for why that's an extension instead of `tearDown()`, and [`docs/without-extension.md`](docs/without-extension.md) for the imperative alternative.

## 🧩 Extending

Custom attributes are two small classes: a DTO implementing `TttAttribute` and an `AttributeHandler` (a public API with a backward-compatibility promise) that applies the state and returns a restorer closure. Handlers must be stateless: all captured state belongs into the closure.

Register custom handlers via a comma-separated `handlers` parameter on the bootstrap extension; there's no need to replace `TttExtension`:

```xml
<extensions>
    <bootstrap class="KonradMichalik\Ttt\TttExtension">
        <parameter name="handlers" value="Vendor\Ext\Tests\Sandbox\MyHandler,Vendor\Ext\Tests\Sandbox\OtherHandler" />
    </bootstrap>
</extensions>
```

Custom handlers run after the built-in ones. A missing class or one that doesn't implement `AttributeHandler` fails fast with an actionable error naming the `handlers` parameter.

See [`docs/non-goals.md`](docs/non-goals.md) for what's deliberately out of scope, and why.

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).

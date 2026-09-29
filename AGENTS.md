# AGENTS.md

Guidance for coding agents working in this repository.

## Project overview

`konradmichalik/ttt` (TYPO3 Testing Terrarium) is a PHPUnit testing toolbox for TYPO3 extension development. State (`$GLOBALS['TYPO3_CONF_VARS']`, environment variables, TYPO3 `Environment`, application context, singletons, backend user, frozen time) is applied declaratively via PHP attributes on test classes and methods. Restoration is guaranteed afterward, even when a test fails, because it hooks into PHPUnit's event system instead of relying on `tearDown()`.

It is a standalone Composer library, not a TYPO3 extension.

- PHP: `~8.2.0 || ~8.3.0 || ~8.4.0 || ~8.5.0`
- PHPUnit: `^10.5 || ^11.0 || ^12.0 || ^13.0`
- TYPO3: `typo3/cms-core` and `typo3/cms-frontend` (`^13.4 || ^14.0`) are `suggest` and `require-dev` only. TYPO3-specific attributes need them.
- License: GPL-3.0-or-later

## Structure

```
src/
  Attribute/     Readonly DTOs implementing TttAttribute
  Handler/       AttributeHandler implementations, one per attribute
  Registry/      SandboxRegistry: reads attributes, applies handlers, restores LIFO
  Subscriber/    PHPUnit event subscribers (apply before setUp, restore after test)
  Http/          Fluent builder for TYPO3 ServerRequest objects
  Assertion/     JsonAssertions trait
  Contract/      ConfigurationValidationContract abstract test case
  Fixture/       Disposable fixtures (images, logs)
  Traits/        Imperative sandboxes for mid-test state changes
  Reflection/, Runtime/
  TttExtension.php  PHPUnit extension entry point
tests/src/       Mirrors src/, plus Integration/ for end-to-end attribute flows
tests/stubs/     Stubs, autoloaded via autoload-dev
docs/            Guides (usage-in-extensions, lifecycle, non-goals)
```

The sandbox mechanism has three layers:
1. Attributes are plain data carriers with no logic.
2. Handlers are stateless. `apply()` snapshots state, mutates it and returns a closure that restores the snapshot.
3. `SandboxRegistry` collects class-level then method-level attributes, runs the matching handlers and restores in reverse order. It continues if a restorer throws and rethrows only the first failure.

`TttExtension::bootstrap()` builds the registry and registers `ApplySandboxSubscriber` (on `PreparationStarted`) and `RestoreSandboxSubscriber` (on `Test\Finished`). `phpunit.xml` registers `TttExtension` so the project's own tests use its attributes.

Adding an attribute:
1. Add a readonly DTO in `src/Attribute/` implementing `TttAttribute`.
2. Add a handler in `src/Handler/` implementing `AttributeHandler`. Keep state in closure variables, never in handler properties.
3. Register the handler in `TttExtension::bootstrap()`.

Consult `docs/usage-in-extensions.md` before changing attribute semantics. Several documented gotchas are deliberate limitations, not bugs.

## Development commands

```bash
composer install
composer lint              # composer normalize, editorconfig and PHP-CS-Fixer (dry run)
composer fix               # apply all fixes
composer sca               # PHPStan
composer migration         # Rector
composer test              # PHPUnit without coverage
composer test:coverage     # PHPUnit with coverage (XDEBUG_MODE=coverage)
```

## Testing

- Test files mirror `src/` one to one. Cross-cutting flows live in `tests/src/Integration/`.
- Use PHPUnit attributes (`#[Test]`, `#[CoversClass(...)]`), not `test`-prefixed method names.
- Single test: `vendor/bin/phpunit -c phpunit.xml --filter testMethodName tests/src/Handler/ConfVarsHandlerTest.php`
- Coverage reports are written to `.build/coverage/` (`clover.xml`, `html/`, `junit.xml`).
- CI runs the tests through a reusable workflow on PHP 8.2 to 8.5 with highest and lowest dependencies.

## Code style and static analysis

- `declare(strict_types=1)` and the license header docblock in every file.
- Attribute DTOs and subscribers are `final readonly class`, handlers and the registry are `final class`.
- PHP-CS-Fixer with `konradmichalik/php-cs-fixer-preset` (`.php-cs-fixer.php`) and the doc block header fixer.
- PHPStan level 8 on `src` and `tests/src` with the PHPUnit extension (`phpstan.neon`).
- Rector targets PHP 8.2 (`rector.php`).
- `.editorconfig` is enforced via `ec`. `composer.json` is normalized with `ergebnis/composer-normalize`.
- `composer-require-checker.json` whitelists TYPO3 core symbols and GD functions used conditionally.
- CI runs CGL on every push through a reusable workflow.

## Git workflow

- Commit format: `<type>: <description>`
- Types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`
- No co-author trailers
- One commit per logical change
- Open a pull request with a description, ideally referencing an issue.

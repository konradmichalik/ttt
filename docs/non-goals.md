# Non-goals

Terrarium deliberately does not attempt the following. Written down once so the reasoning doesn't have to be re-litigated across future issues and PRs.

- **`#[Retry(n)]`** — no test re-execution API exists for PHPUnit extensions to hook into.
- **Inline data-provider attributes** — superseded by PHPUnit's native `#[TestWith]` / `#[TestWithJson]`; duplicating that is pure overlap.
- **`Requires*` / `Forbids*` attributes** — already covered by [`eliashaeussler/phpunit-attributes`](https://github.com/eliashaeussler/phpunit-attributes); Terrarium doesn't duplicate it.
- **Constant/function monkey-patching** — would require Runkit7, which isn't a realistic dependency for a testing toolbox.
- **`#[MockService]` with expectations** — attribute arguments must be constant expressions; a mock carrying behavioral expectations (`expects()`, `willReturn()`, ...) cannot be expressed as one.
- **Runtime container service replacement in functional tests** — Symfony containers are compiled and frozen by the time a functional test runs; `#[WithSingleton]`-style replacement isn't available there. See also the `FunctionalTestCaseGuard` in `#[WithEnvironment]`/`#[InApplicationContext]`.
- **Attributes needing the test instance (`$this`)** — the `AttributeHandler` contract only ever receives the attribute DTO, by design (handlers are stateless and reusable independent of any specific test class). An attribute that needs the running test instance itself can't be expressed through this contract.
- **A `ConnectionPool` / `QueryBuilder` fake** — a different order of complexity than everything else in this package; if it's ever built, it belongs in a separate package, not bolted onto Terrarium.

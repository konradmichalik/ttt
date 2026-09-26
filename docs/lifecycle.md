# Lifecycle

```
setUp() (+ #[Before]/#[PreCondition] hooks)
    ↓
attributes applied            (PHPUnit\Event\Test\Prepared)
    ↓
test method body
    ↓
tearDown() (+ #[After] hooks), attribute state still active
    ↓
attributes restored            (PHPUnit\Event\Test\Finished, fires even on failure/error)
```

`setUp()` never observes Terrarium-managed state; `tearDown()` still does, since restoration runs after it. By then, though, the test's result is already determined, so nothing an attribute does in `tearDown()` can affect a passed/failed outcome. If `setUp()` needs to see sandboxed state, apply it imperatively instead: see [`docs/without-extension.md`](without-extension.md).

## Process isolation

With `#[RunInSeparateProcess]`, `#[RunTestsInSeparateProcesses]` or `processIsolation="true"`, PHPUnit runs the test body in a child process and forwards its events to the parent afterwards. Terrarium receives `Test\Prepared` in the parent, after the test body already finished, so attributes cannot take effect there.

Instead of silently running the test without its declared state, Terrarium reports every attributed test under process isolation as a PHPUnit warning (`Terrarium attributes on ... have no effect under process isolation`). Tests without Terrarium attributes are unaffected. When a test needs process isolation, apply the state imperatively inside the test body, which runs in the child: see [`docs/without-extension.md`](without-extension.md).

## Parallel execution

`#[WithEnvVar]` (`putenv()`/`$_ENV`/`$_SERVER`) and any attribute touching process-global PHP state (e.g. a future timezone/locale attribute via `date_default_timezone_set()`/`setlocale()`) mutate state shared by the whole PHP process, not per-test state. Safe under `paratest` (one process per worker), unsafe under any runner that shares a process across tests running concurrently. This is a known constraint of process-global PHP APIs, not something Terrarium can work around.

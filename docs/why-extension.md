# Why an extension instead of `tearDown()`?

The restore logic is driven by PHPUnit's event system (`Test\Finished` fires for **every** test, regardless of outcome). Hand-written `tearDown()` cleanup can be skipped by hard errors and leak state into subsequent tests: Terrarium can't.

See [`docs/lifecycle.md`](lifecycle.md) for how attribute application and restoration are wired into PHPUnit's event sequence.

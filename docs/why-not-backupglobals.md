# Why not `#[BackupGlobals]`?

PHPUnit's built-in `#[BackupGlobals(true)]` already snapshots and restores `$GLOBALS`. Terrarium is a different tool for a different granularity:

| | `#[BackupGlobals(true)]` | Terrarium |
|---|---|---|
| Granularity | The entire `$GLOBALS` superglobal | Per-attribute: exactly the keys you declare |
| Merge semantics | Full snapshot/restore, no merging | Deep merge on top of the existing value |
| `putenv()` / `$_ENV` / `$_SERVER` | Not covered | `#[WithEnvVar]` restores all three channels |
| Non-serializable values | `$GLOBALS` must be serializable for the snapshot to work | Any object reference works, nothing is serialized |
| Cost | Serializes/deserializes all of `$GLOBALS` per test | Only touches the keys the attribute declares |

Use `#[BackupGlobals(true)]` when you genuinely don't know what a test might touch. Use Terrarium's attributes when you know exactly which keys matter, which is the common case for TYPO3 extension tests.

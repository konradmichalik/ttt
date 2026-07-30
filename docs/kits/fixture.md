# Fixture kit

Disposable test fixtures: [`ImageFixtures`](../../src/Fixture/ImageFixtures.php) creates GD-generated PNGs, [`LogFixtures`](../../src/Fixture/LogFixtures.php) writes line-based log files.

## Example

```php
$png = ImageFixtures::createPng(64, 64);          // disposable GD test image
LogFixtures::write($path, ['line one', 'line two']);
```

<details>
<summary>More examples</summary>

### Custom color and destination path

```php
$png = ImageFixtures::createPng(width: 32, height: 32, rgb: [255, 0, 0], path: '/tmp/fixture.png');
```

### Log fixture with a nested destination directory

```php
LogFixtures::write('/tmp/logs/nested/app.log', ['[INFO] started', '[ERROR] failed']);
// Intermediate directories are created automatically.
```

</details>

## Migrating from hand-written code

Temp directories for `Environment` purposes are handled by [`#[WithEnvironment]`](../attributes/with-environment.md) (incl. cleanup). For log and image fixtures, replace hand-rolled "create test image"/"write log" helpers with:

```php
use KonradMichalik\Ttt\Fixture\{ImageFixtures, LogFixtures};

$png = ImageFixtures::createPng(64, 64);
LogFixtures::write($logPath, ['[2026-07-14] ERROR foo']);
```

# Fixture kit

Disposable test fixtures: [`ImageFixtures`](../../src/Fixture/ImageFixtures.php) creates GD-generated PNGs, [`LogFixtures`](../../src/Fixture/LogFixtures.php) writes line-based log files, [`JsonResponseFixture`](../../src/Fixture/JsonResponseFixture.php) builds a PSR-7 JSON response (requires `typo3/cms-core`).

## Example

```php
$png = ImageFixtures::createPng(64, 64);          // disposable GD test image
LogFixtures::write($path, ['line one', 'line two']);
$response = JsonResponseFixture::create(['numFound' => 3]);   // PSR-7 response, status 200
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

### JSON response with a status code

```php
$response = JsonResponseFixture::create(['error' => 'unavailable'], 503);
```

</details>

## Migrating from hand-written code

Temp directories for `Environment` purposes are handled by [`#[WithEnvironment]`](../attributes/with-environment.md) (incl. cleanup). For log and image fixtures, replace hand-rolled "create test image"/"write log" helpers with:

```php
use KonradMichalik\Ttt\Fixture\{ImageFixtures, LogFixtures};

$png = ImageFixtures::createPng(64, 64);
LogFixtures::write($logPath, ['[2026-07-14] ERROR foo']);
```

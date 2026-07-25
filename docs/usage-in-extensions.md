# ttt in eigenen TYPO3-Extensions nutzen

Migrations- und Nutzungsanleitung für den Einsatz von `konradmichalik/ttt` (ttt = TYPO3 Testing Terrarium) in den bestehenden Extension-Test-Suiten (routing, environment-indicator, ai-mate, letter-avatar, file-sync, request-profiler, dump-server, solr-dashboard-widgets).

---

## 1. Installation & Setup

```bash
composer require --dev konradmichalik/ttt
```

Während der lokalen Entwicklung am Paket selbst (vor dem ersten Packagist-Release) per Path-Repository:

```json
"repositories": [
    { "type": "path", "url": "../ttt" }
],
"require-dev": {
    "konradmichalik/ttt": "@dev"
}
```

Extension einmalig in der PHPUnit-Konfiguration der Extension registrieren (z. B. `Tests/Build/UnitTests.xml` bzw. `phpunit.xml`):

```xml
<extensions>
    <bootstrap class="KonradMichalik\Ttt\TttExtension"/>
</extensions>
```

**Wichtig:** Die Registrierung gehört nur in die **Unit-Test**-Konfiguration. Functional Tests laufen weiter unverändert über `typo3/testing-framework` — Terrarium ersetzt dort nichts und stört dort nichts (die Subscriber reagieren nur auf Tests mit Terrarium-Attributen).

---

## 2. Migrations-Rezepte (vorher → nachher)

### 2.1 TYPO3_CONF_VARS-Setup mit tearDown-Cleanup

Das häufigste Muster im Portfolio (233 Fundstellen).

**Vorher:**

```php
protected function setUp(): void
{
    $this->backup = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['my_ext']['configuration'] = ['color' => '#f00'];
}

protected function tearDown(): void
{
    $GLOBALS['TYPO3_CONF_VARS'] = $this->backup;
}
```

**Nachher:**

```php
use KonradMichalik\Ttt\Attribute\WithTypo3ConfVars;

#[WithTypo3ConfVars(['EXTCONF' => ['my_ext' => ['configuration' => ['color' => '#f00']]]])]
final class HandlerTest extends TestCase
{
    // setUp/tearDown entfallen komplett
}
```

Regeln: Klassen-Attribut gilt für alle Tests der Klasse, Methoden-Attribute mergen obendrauf (Methode gewinnt bei Konflikten), das Attribut ist wiederholbar. Der Merge ist ein Deep-Merge — es reicht, den tatsächlich benötigten Teilbaum anzugeben. Restore läuft garantiert, auch wenn der Test hart fehlschlägt.

Für Manipulationen **mitten im Test** (z. B. Konfiguration ändern und erneut auflösen) gibt es die imperative Variante:

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
    public function reactsToChangedConfiguration(): void
    {
        $this->setTypo3ConfVars(['EXTCONF' => ['my_ext' => ['mode' => 'a']]]);
        // ... erste Assertion ...
        $this->setTypo3ConfVars(['EXTCONF' => ['my_ext' => ['mode' => 'b']]]);
        // ... zweite Assertion ...
    }
}
```

### 2.2 Environment::initialize in setUpBeforeClass

Der 12× duplizierte Block (letter-avatar, ai-mate, request-profiler, routing).

**Vorher:**

```php
public static function setUpBeforeClass(): void
{
    $projectPath = sys_get_temp_dir() . '/my-ext-test-' . uniqid();
    mkdir($projectPath . '/var', 0777, true);
    Environment::initialize(
        new ApplicationContext('Testing'),
        true, true,
        $projectPath, $projectPath . '/public', $projectPath . '/var',
        $projectPath . '/config', $projectPath . '/public/index.php', 'UNIX'
    );
}
```

**Nachher:**

```php
use KonradMichalik\Ttt\Attribute\WithEnvironment;

#[WithEnvironment(context: 'Testing')]
final class PathUtilityTest extends TestCase {}
```

Das temporäre Projektverzeichnis (inkl. `public/`, `var/`, `config/`) wird pro Test erzeugt und danach gelöscht; ein vorher initialisiertes Environment wird exakt wiederhergestellt. Wer ein festes Verzeichnis braucht: `#[WithEnvironment(projectPath: '/pfad', temporaryProjectPath: false)]`.

**Achtung Semantik-Wechsel:** `setUpBeforeClass` lief einmal pro Klasse, das Attribut läuft pro Test. Das ist gewollt (Isolation), kostet aber minimal Laufzeit durch mkdir/rmdir. Bei sehr großen Klassen mit ausschließlich lesenden Zugriffen auf die Pfade ist das vernachlässigbar; falls es je messbar wird, lässt sich ein Klassen-Scope im Paket nachrüsten.

### 2.3 DevelopmentContextTrait (request-profiler)

**Vorher:**

```php
$this->inDevelopmentContext(function (): void {
    self::assertTrue($this->subject->isProfilingActive());
});
```

**Nachher:**

```php
use KonradMichalik\Ttt\Attribute\InApplicationContext;

#[Test]
#[InApplicationContext('Development')]
public function profilingIsActiveInDevelopmentContext(): void
{
    self::assertTrue($this->subject->isProfilingActive());
}
```

Der Testkörper wird flach — kein Closure-Wrapping mehr. Voraussetzung: initialisiertes Environment (in Kombination einfach `#[WithEnvironment]` auf die Klasse setzen; die Handler laufen in Deklarationsreihenfolge, Klassen-Attribute vor Methoden-Attributen). Für scoped Wechsel *innerhalb* eines Tests bleibt das alte callable-Muster über den Trait `ApplicationContextSwitcher` verfügbar.

### 2.4 setSingletonInstance / purgeInstances (request-profiler u. a.)

**Vorher:**

```php
protected function setUp(): void
{
    GeneralUtility::setSingletonInstance(CacheManager::class, new NullCacheManager());
}

protected function tearDown(): void
{
    GeneralUtility::purgeInstances(); // wirft ALLE Singletons weg, nicht nur den eigenen
}
```

**Nachher:**

```php
use KonradMichalik\Ttt\Attribute\WithSingleton;

#[Test]
#[WithSingleton(CacheManager::class, new NullCacheManager())]
public function usesInjectedCacheManager(): void {}
```

Zwei Verbesserungen gegenüber dem Handmuster: Es wird die *komplette vorherige Singleton-Map* restauriert (kein Kollateralschaden durch `purgeInstances()`), und dank PHP 8.1+ sind `new`-Ausdrücke direkt im Attribut erlaubt. Alternativ geht ein Class-String, der ohne Konstruktor-Argumente instanziiert wird: `#[WithSingleton(Foo::class, FakeFoo::class)]`.

### 2.5 BE_USER-Stubs (environment-indicator, file-sync)

**Vorher:**

```php
$user = $this->createMock(BackendUserAuthentication::class);
$user->method('isAdmin')->willReturn(true);
$GLOBALS['BE_USER'] = $user;
// tearDown: unset($GLOBALS['BE_USER']);
```

**Nachher:**

```php
#[Test]
#[WithBackendUser(admin: true, uid: 42)]
public function showsIndicatorForAdmins(): void {}
```

Der Stub ist eine echte `BackendUserAuthentication`-Subklasse mit gefülltem `user`-Array — `isAdmin()`, `$user->user['uid']` etc. funktionieren ohne Mock-Konfiguration. Wo Tests spezielles Mock-Verhalten brauchen (z. B. `check()`-Erwartungen), beim Mock bleiben; das Attribut deckt den 80-%-Fall „es muss halt ein (Admin-)User da sein" ab.

### 2.6 Zeitabhängige Tests

**Nachher (neu möglich):**

```php
#[Test]
#[FreezeTime('2026-07-14T12:00:00Z')]
public function calculatesExpiryFromNow(): void
{
    // Context-'date'-Aspect UND $GLOBALS['EXEC_TIME']/ACCESS_TIME sind gepinnt
}
```

Deckt beide Zeitquellen ab, die TYPO3-Code typischerweise nutzt. Code mit rohem `time()`/`new \DateTime()` bleibt davon unberührt — solcher Code ist der eigentliche Refactoring-Kandidat (Context-Aspect injizieren).

### 2.7 Handgebaute ServerRequests (routing: 54×, request-profiler: 20×)

**Vorher:**

```php
$request = (new ServerRequest('https://example.com/api/count', 'GET'))
    ->withQueryParams(['q' => 'x'])
    ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(...));
```

**Nachher:**

```php
use KonradMichalik\Ttt\Http\Requests;

$request = Requests::get('https://example.com/api/count')
    ->withQueryParams(['q' => 'x'])
    ->withRemoteAddress('10.0.0.1')
    ->build();
```

`normalizedParams` wird automatisch als Attribut gesetzt (abschaltbar via `->withoutNormalizedParams()`), JSON-Bodies via `->withJsonBody([...])` inkl. Content-Type-Header. Für Site-/Routing-Attribute: `->withAttribute('site', $site)`.

### 2.8 JSON-Asserts (ai-mate MCP, routing OpenAPI, request-profiler Artefakte)

**Vorher:**

```php
$decoded = json_decode((string) $response->getBody(), true);
self::assertSame(42, $decoded['result']['items'][0]['uid']);
```

**Nachher:**

```php
use KonradMichalik\Ttt\Assertion\JsonAssertions;

final class McpResponseTest extends TestCase
{
    use JsonAssertions;

    #[Test]
    public function returnsItems(): void
    {
        self::assertJsonPath((string) $response->getBody(), 'result.items.0.uid', 42);
        self::assertJsonHasPaths($response->getBody(), ['result.schemaVersion', 'result.items']);
    }
}
```

Bei fehlenden Pfaden nennt die Failure-Message das erste fehlende Segment — deutlich schnellere Diagnose als ein `Undefined array key`.

### 2.9 Validierungs-Contracts (environment-indicator: 58 Modifier-Methoden)

Statt handgeschriebener `testValidateConfigurationFailsWithMissingColor()`-Serien:

```php
use KonradMichalik\Ttt\Contract\ConfigurationValidationContract;

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

Generiert automatisch: fehlender Pflicht-Key, falscher Typ je Key, Unter-/Überschreitung je Range. Extension-spezifische Sonderfälle (z. B. Hex-Format von `color`) als zusätzliche normale `#[Test]`-Methoden in derselben Klasse ergänzen — der Contract ist die Basis, nicht die Obergrenze.

### 2.10 sys_get_temp_dir-Handling & Fixtures (ai-mate: 17×)

Temp-Verzeichnisse für Environment-Zwecke übernimmt `#[WithEnvironment]` (inkl. Cleanup). Für Log- und Bild-Fixtures:

```php
use KonradMichalik\Ttt\Fixture\{ImageFixtures, LogFixtures};

$png = ImageFixtures::createPng(64, 64);                      // env-indicator, letter-avatar
LogFixtures::write($logPath, ['[2026-07-14] ERROR foo']);     // ai-mate LogsCommand
```

---

## 3. Empfohlene Reihenfolge je Extension

| Extension | Zuerst migrieren | Attribute/Kits |
|---|---|---|
| **letter-avatar** (Pilot 1) | 5× `Environment::initialize`, 67 ConfVars-Spots | `WithEnvironment`, `WithTypo3ConfVars`, `ImageFixtures` |
| **request-profiler** (Pilot 2) | `DevelopmentContextTrait`, 12 Singleton-Paare | `InApplicationContext`, `WithSingleton`, `JsonAssertions` |
| environment-indicator | Modifier-Validierung (58 Methoden), BE_USER-Stubs | `ConfigurationValidationContract`, `WithBackendUser`, `WithTypo3ConfVars` |
| ai-mate | Temp-Path-Trait, MCP-Asserts, Log-Fixtures | `WithEnvironment`, `JsonAssertions`, `LogFixtures` |
| routing | Request-Konstruktion (54×) | `Requests`, `JsonAssertions` |
| dump-server | ConfVars, EnvVar-Flags | `WithTypo3ConfVars`, `WithEnvVar` |
| file-sync | BE_USER-Stubs | `WithBackendUser` |
| solr-dashboard-widgets | JSON-Fixtures | `JsonAssertions` |

Die beiden Piloten decken zusammen alle Attribut-Typen ab — erst nach deren grünem Durchlauf (inkl. Coverage-Diff gegen die phpcov-Baseline) breit ausrollen.

---

## 4. Gotchas & Grenzen

- **Restore-Garantie:** läuft über PHPUnits `Test\Finished`-Event, das für jeden Test feuert — auch bei Failures und Errors. Eigene tearDowns für Terrarium-verwaltete Zustände sind nicht nötig und sollten beim Migrieren gelöscht werden (doppeltes Restore ist harmlos, aber toter Code).
- **Reihenfolge:** Klassen-Attribute werden vor Methoden-Attributen angewendet, Restore läuft LIFO. `#[WithEnvironment]` auf der Klasse + `#[InApplicationContext]` auf der Methode ist damit die korrekte Kombination.
- **Environment-Limitation:** War das Environment vor dem Test *nicht* initialisiert, bleibt es nach dem Test initialisiert (typed static properties lassen sich nicht de-initialisieren). In Suiten, die komplett auf `#[WithEnvironment]` setzen, ist das irrelevant; gemischte Suiten sollten keine Tests enthalten, die auf *un*initialisiertes Environment angewiesen sind.
- **getenv-Caveat:** `#[WithEnvVar]` wirkt auf per-Request-Auswertungen. `getenv()`-Aufrufe, die zur Cache-Build-Zeit evaluiert werden (Stichwort `ext_localconf.php`), erreicht es nicht — bekanntes Verhalten aus dem request-profiler.
- **Functional Tests:** unangetastet lassen. Terrarium ist die Unit-Sandbox; DB-Fixtures, Extension-Loading und `FunctionalTestCase` bleiben Sache von typo3/testing-framework.
- **Attribut-Instanzen:** `new` in Attribut-Argumenten (für `WithSingleton`) erfordert PHP ≥ 8.1 — im Portfolio überall gegeben.
- **Mid-Test-Änderungen:** Attribute wirken vor `setUp()`. Wer Zustand *während* des Tests ändern muss, nutzt die Traits (`ConfVarsSandbox`, `ApplicationContextSwitcher`) statt der Attribute.

---

## 5. Definition of Done pro Repo-Migration

1. phpcov-Baseline vor der Migration erzeugt.
2. Alle handgeschriebenen setUp/tearDown-Blöcke für Terrarium-verwaltete Zustände entfernt.
3. Testsuite grün auf der vollen Matrix (PHP × PHPUnit × TYPO3 13/14).
4. Coverage-Diff gegen Baseline: keine Verluste.
5. Repo-lokale Helfer-Traits gelöscht, sofern durch Terrarium ersetzt (`WithTemporaryVarPath`, `DevelopmentContextTrait`, `CreatesTestImageTrait`, …).

---

## 6. Security- & Performance-Notizen (Review-Ergebnis)

**Security-Härtungen im Paket (v0.2.x):**

- Der Cleanup temporärer Projektverzeichnisse folgt **keinen Symlinks** — Links werden entfernt statt in ihr Ziel zu rekursieren. Ein Test, der (auch versehentlich) einen Symlink ins Repo anlegt, kann so keinen Datenverlust außerhalb der Sandbox auslösen.
- Temporäre Pfade nutzen `random_bytes` statt vorhersagbarem `uniqid`; existiert der berechnete Pfad wider Erwarten bereits (Pre-Creation-/Symlink-Race in geteilten `/tmp`-Umgebungen), bricht der Handler mit Exception ab statt den fremden Pfad zu übernehmen.
- Verzeichnisse werden mit `0700` statt `0777` angelegt; fehlgeschlagenes `mkdir` wirft statt still weiterzulaufen.
- `#[WithEnvVar]` validiert den Variablennamen (`[A-Za-z_][A-Za-z0-9_]*`) — kein `=`-Injection-Vektor über `putenv()`.

**Bewusst akzeptierte Grenzen (Dev-Only-Threat-Model):**

- Attribute sind Entwickler-Code: Class-Strings in `#[WithSingleton]` und Datumsstrings in `#[FreezeTime]` werden nicht weiter sandboxed — wer Testcode schreibt, führt ohnehin Code aus.
- Die `\assert()`-Typ-Narrowings in den Handlern sind Belt-and-Braces hinter dem `supports()`-Guard der Registry; bei deaktivierten Assertions greift weiterhin die Typprüfung der jeweiligen TYPO3-API (z. B. `setSingletonInstance`).

**Performance:**

- Die Attribut-Auflösung (Reflection) läuft für *jeden* Test und ist deshalb memoized: pro Klasse bzw. pro Klasse+Methode wird genau einmal reflektiert; Wiederholungsläufe (DataProvider-Cases!) treffen nur noch den Cache. Attribut-Instanzen sind readonly DTOs und damit gefahrlos wiederverwendbar.
- Tests ohne Terrarium-Attribute kosten pro Test einen Cache-Lookup plus leeren Restore-Aufruf — im Mikrosekundenbereich, kein messbarer Suite-Overhead.
- Der teuerste Handler ist `#[WithEnvironment]` (mkdir/rmdir pro Test). Für I/O-sensible Riesenklassen ggf. auf Klassen-Ebene sparsam einsetzen; ein optionaler Klassen-Scope ist als spätere Erweiterung vorgesehen.

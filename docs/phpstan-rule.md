# PHPStan rules

The rules that point hand-written test state (`putenv()`, `$GLOBALS['TYPO3_CONF_VARS']`, `$GLOBALS['TCA']`) to the matching ttt attribute live in a separate package: [`konradmichalik/ttt-phpstan`](https://github.com/konradmichalik/ttt-phpstan).

```bash
composer require konradmichalik/ttt-phpstan --dev
```

With [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer) installed there is nothing more to configure. The package documents its rules, what it deliberately does not report and how to ignore a finding.

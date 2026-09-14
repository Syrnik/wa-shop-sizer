# AGENTS.md

## Commit Messages

All commits must follow the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) specification.

Format: `<type>[optional scope]: <description>`

Common types: `feat`, `fix`, `chore`, `refactor`, `docs`, `test`, `ci`.

## Changelog

The `CHANGELOG.md` file must follow the [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format.

- Add entries under `[Unreleased]` during development.
- On release, rename `[Unreleased]` to the version number with the release date.
- Sections within a version: `Added`, `Changed`, `Deprecated`, `Removed`, `Fixed`, `Security`.

## Tests

PHPUnit tests live in `tests/`, bootstrap `tests/init.php`. `composer install` is not needed — the
plugin has no dev dependencies; run with a globally installed PHPUnit from the plugin root:

```
phpunit -c phpunit.xml
phpunit -c phpunit.xml tests/shopSizerPluginShippingPackageTest.php   # single file
```

All logic lives in the single class `lib/shopSizerPlugin.class.php`, instantiated through
`tests/shopSizerPluginTestDouble.php` (`checkUpdates()` is a no-op, avoiding the DB-backed install
path; settings are injected directly via `setTestSettings()` rather than fetched from the DB).
`tests/shopSizerPluginTestAppSettingsModelFake.php` stands in for `waAppSettingsModel` when a test
needs to exercise `saveSettings()`'s DB write path.

## PHP Compatibility (Psalm)

Run from the plugin root with the globally installed Psalm:

```
psalm -c psalm74.xml
psalm -c psalm85.xml
```

Both configs scan `lib/` at `errorLevel="4"`, bootstrap via `tests/psalm-init.php` (autoloads the
plugin class through `wa('shop')`, no DB writes). `psalm-baseline.xml` absorbs one specific,
pre-existing false positive — `waHtmlControl::registerControl()`'s docblock uses the legacy
`@param callback` pseudo-type, which Psalm can't match against the standard `[$this, 'method']`
callback array — not a real bug. A clean run means no errors on either PHP 7.4 or PHP 8.5 target.
Regenerate the baseline with `--set-baseline=psalm-baseline.xml` only when intentionally accepting
a new suppression, not to hide a real regression.

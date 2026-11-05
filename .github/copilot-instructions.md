# Shared PHPUnit Composer dependency

- Install `rotdrop/nextcloud-app-phpunit` as a development dependency in
  `vendor-bin/phpunit`, not in the namespace-scoped `composer-wrapped.json`.
  Consult the [upstream README](https://github.com/rotdrop/nextcloud-app-phpunit/blob/HEAD/README.md)
  for current Composer installation and integration guidance.
- Keep shared helpers app-neutral. Do not hard-code either consumer's scoped
  toolkit namespace in shared code. Keep Members-specific database naming in
  `tests/phpunit/DatabaseProvider.php` and preserve the inherited constructor,
  including its temporary-manager and concrete executable-finder dependencies.
- Preserve the server bootstrap order: initialize Nextcloud and its container;
  load the PHPUnit bin-project, app, and scoped autoloaders; define app
  constants; load/register apps; then resolve the provider. The shared
  procedural `bootstrap.php` must not be added to Composer `autoload.files`;
  helpers load it lazily after their prerequisites are available.
- Keep `tests/phpunit/bootstrap-standalone.php` independent of Nextcloud and
  explicit shared-bootstrap loading. The server and standalone PHPUnit
  configurations must remain separate, and merely loading the bin-project
  autoloader must not require app constants or toolkit aliases.
- When changing shared behavior or integration, inspect both CafeVDB and
  Members independently. Members is optional for CafeVDB; run app-specific
  checks in separate PHP processes because constants and toolkit aliases are
  process-wide. Keep app-only adapters and configuration outside the package.

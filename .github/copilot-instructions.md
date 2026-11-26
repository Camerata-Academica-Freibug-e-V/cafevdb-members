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

## Branching

- Base branches and pull requests must target the repository's remote default branch (check with `gh repo view --repo OWNER/REPO --json defaultBranchRef`), not a possibly stale local branch or saved project metadata, unless the user explicitly specifies another. Fetch it before branching.

## Shared PHPUnit helpers

- Before changing code inside a git subrepo, read its `.gitrepo` to identify the boundary and upstream. Treat its contents as reusable upstream code, not app-local code; never edit `.gitrepo` manually.
- `OCA\RotDrop\Tests\` is provided by the `rotdrop/nextcloud-app-phpunit` Composer development dependency in `vendor-bin/phpunit`, not by app-local source or the namespace-scoped production dependencies. Follow that package's README for dependency setup and helper integration.
- Keep shared helpers consumer-neutral: do not hard-code either `OCA\CAFEVDB\Wrapped\OCA\RotDrop\Toolkit` or `OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit` in DTO superclasses, traits, or constructor types. Select the toolkit using `PHPUNIT_NC_APP_NAMESPACE`, preserve DTO inheritance and logger behavior, and make the neutral finder resolve to the consuming app's existing scoped service instance while retaining concrete finder typing.
- Before resolving the shared provider, initialize Nextcloud, load the PHPUnit bin-project, app and scoped autoloaders, define the app constants, and load the app so its services are registered.
- PHP code also pass cleanly through PHP Mess Detector and PHP_CodeSniffer, using the tools installed under `vendor-bin/` and the rules defined in `.phpmd.xml` and `.phpcs.xml`. Use the same rules locally and in CI, without extra workflow-only exclusions or ignored warnings for tests.
- PHPUnit tests should also pass cleanly through PHP Mess Detector and PHP_CodeSniffer, using the tools installed under `vendor-bin/` and the rules defined in `.phpmd.xml` and `.phpcs.xml`. Use the same rules locally and in CI, without extra workflow-only exclusions or ignored warnings for tests.

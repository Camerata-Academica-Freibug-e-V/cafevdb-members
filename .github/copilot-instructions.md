# Shared git-subrepo boundaries

- Before changing a git subrepo, identify its boundary and upstream from its
  `.gitrepo`. Treat its contents as reusable upstream code, not app-local code.
  Do not edit `.gitrepo` manually; use `git subrepo` for synchronization.
- `tests/phpunit/lib` comes from
  [rotdrop/nextcloud-app-phpunit](https://github.com/rotdrop/nextcloud-app-phpunit)
  and is shared with [CafeVDB](https://github.com/rotdrop/cafevdb). Never hard-code
  `OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit` or
  `OCA\CAFEVDB\Wrapped\OCA\RotDrop\Toolkit` into its reusable helpers, DTO
  superclass, traits, or constructor types. App-local scoped namespaces are
  valid in consumer wiring outside the subrepo. Explicit namespace strings in
  compatibility-test inputs or documentation are not runtime coupling.
- Consult `tests/phpunit/lib/README.md` and the
  [current upstream README](https://github.com/rotdrop/nextcloud-app-phpunit/blob/HEAD/README.md)
  for app-neutral integration, rather than copying an app-specific namespace
  into shared code. As verified on 2026-11-05,
  [shared fix #2](https://github.com/rotdrop/nextcloud-app-phpunit/pull/2) is still
  open, draft, and unmerged; the default-branch README does not yet document
  that integration. Recheck its status and README before relying on it.
  Once available, select the scoped toolkit from `PHPUNIT_NC_APP_NAMESPACE`
  through the shared integration. Retain toolkit DTO inheritance, logger
  behavior, and concrete `ExecutableFinder` typing. The proposed neutral
  `OCA\RotDrop\Tests\Toolkit\Service\ExecutableFinder` must resolve to the
  consuming app's existing scoped service instance, not construct a second
  finder; a PHP class alias alone does not ensure DI service identity.
- Preserve bootstrap prerequisites: initialize Nextcloud and its server
  container, load Composer and scoped autoloaders, define the app constants
  (including `PHPUNIT_NC_APP_NAMESPACE` and `PHPUNIT_NC_APP_NAME`), then load the
  app/register its services before resolving the provider. Follow the current
  shared README for when to load the shared integration.
- Keep Members-specific database naming and behavior in the app-local
  `tests/phpunit/DatabaseProvider.php` subclass, not in `tests/phpunit/lib`.
  Preserve compatibility with its inherited constructor, including the
  required temporary-manager and concrete finder dependencies; the subclass
  currently overrides only database naming.
- When changing shared behavior, discover and inspect both consumers' current
  default branches; do not assume branch names. Verify each independently,
  including Members' app-specific `DatabaseProvider` subclass and CafeVDB
  without Members installed. Members is optional for CafeVDB: shared helpers
  must not require it. Run consumer checks in separate PHP processes because
  app constants and toolkit aliases are process-wide.
- Before upstream export, review the intended subrepo diff for
  consumer-specific namespaces and dependencies. Keep app-only adapters and
  configuration outside the subrepo. Describe required consumer integration,
  what was actually verified, and any validation limitations explicitly.

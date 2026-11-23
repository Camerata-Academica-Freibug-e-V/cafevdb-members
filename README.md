# CAFeVDB Members App

A Nextcloud app giving the members of a project-based orchestra
read-only access to a selected subset of their own data stored in the
orchestra's [CAFeVDB](https://github.com/rotdrop/cafevdb) database.

## Background

CAFeVDB is an orchestra-management app for orchestras which work
project by project (there are no weekly rehearsals). Its use is
restricted to the management board of the orchestra.

This app is the counterpart for the club members. After logging in,
each member can see, but not change:

- their contact information,
- their project participation,
- their instrument insurances,
- authorized files from CAFeVDB's database-backed storages.

Database-backed files initially appear under the movable
`MEMBER_ROOT_FOLDER/USER_ID/` mount in the member's Files area, where
`MEMBER_ROOT_FOLDER` is the configured member-data team-folder (for example,
`orchestra-members`). Project participant storage roots are presented as
`MEMBER_ROOT_FOLDER/USER_ID/Projects/PROJECT_NAME/` before localization; other
database storage identifiers retain their hierarchy beneath the mount. The
member root folder must be configured and accessible to the user. The view is
read-only and uses the personalized database views to limit entries to those
authorized for the logged-in member. A user-moved mount keeps its saved path.
Generated folder labels, such as `Projects` and `Finance`, are translatable
through the Members catalogue using the orchestra's configured language.
Project names, database directory-entry names, and the mount path are not
translated.

While authenticated, database-storage sources are cached for the lifetime of a
storage instance and refreshed whenever Nextcloud checks a directory for
changes. This keeps repeated metadata lookups from re-querying the full
personalized storage view while allowing cached folders to reflect added or
removed authorized sources even when their timestamps do not change.

When a user opens an authenticated Nextcloud page, the browser also requests a
recursive scan of that user's database mount, including a mount moved to another
path. Successful scans are limited to one every ten minutes per user and
concurrent requests are serialized. Failed or unavailable scans are retried.
File locking must be enabled for this automatic scan. WebDAV-only clients do
not trigger it, although directory listings are still checked when accessed.

When a user's authorization credentials are unavailable (for example during
cron scans), an already registered mount is retained as unavailable. Nextcloud's
standard scanner skips this placeholder: the existing mount/root/storage IDs
and file-cache rows, including file IDs used by links in Markdown, are preserved.
Cached directories and file contents are not exposed offline, and the mount
cannot be moved in that state. Its previously saved path is retained verbatim,
without regenerating localized directory names.

Authenticated access reconnects the real storage to the same cache. A missing
row-access token during authenticated setup intentionally removes the mount;
temporary decryption or database failures retain the unavailable placeholder.
No placeholder is created for a user without a previous mount. Incomplete cache
entries remain incomplete until authenticated scanning resumes, so Nextcloud's
background job may continue reporting that the user has unscanned files.

This preservation-only approach does not reconstruct or refresh the filesystem
offline. The companion app's related discussion is tracked in
[cafevdb#123](https://github.com/Camerata-Academica-Freibug-e-V/cafevdb/issues/123);
its provider would need its own integration before retiring the server workaround.

## Relation to CAFeVDB

The members app is a separate app with its own copy of the required
entities and types. CAFeVDB is **not** needed to install, build or test
this app, and the PHPUnit suite runs without it.

For actual use, however, CAFeVDB is required: it populates and
maintains the data shown by this app. At runtime the members app
obtains encryption keys, translations and project events (e.g. project
creation and deletion, used to maintain per-project groups and
folders) from CAFeVDB through small interfaces and adapters which are
resolved lazily. Without CAFeVDB installed on the same Nextcloud
instance the members app can be installed, but it is of no use.

## Personalized views connection

Enable/regenerate the personalized views in CafeVDB. New installations use
**Use CafeVDB views database** in Members admin settings by default. Members reads
CafeVDB's versioned, non-secret `cloudConnectorDescriptor` on each request:
the effective database, expected MariaDB account, personalized-view enablement
and configuration revision. This does not copy passwords or overwrite settings.
Existing non-empty Members database names remain explicit overrides; disable
inheritance to edit an override, or enable it to follow CafeVDB without erasing
the old value. No setting is automatically written back to CafeVDB.

Admin settings show the effective and producer database names, expected and
authenticated MariaDB accounts (`CURRENT_USER()`), and configuration/access
warnings. **Check connector configuration** refreshes these diagnostics.
The account host is an authentication identity, not the database server address.
When the connection fails, account comparison is explicitly unverified.
Older, disabled or unavailable CafeVDB versions retain the existing Members
database setting with a warning. With no usable database, configuration is
reported as incomplete. Open CafeVDB sharing settings once after upgrading to
initialize its descriptor. A changed descriptor is used on subsequent requests;
already-open connections are not migrated, and CafeVDB still removes old views
when changing schemas, so coordinate database switches.

Configure the views account and password in CafeVDB. When CafeVDB has separate
cloud connector credentials configured, Members reads the encrypted credentials
and uses their MariaDB endpoint (including port or socket), username and password.
This also works when Nextcloud uses PostgreSQL, SQLite, or another server.
Changing or resetting the endpoint in CafeVDB changes the connection used by
Members on subsequent requests.

If upgrading from a version that stored a Members-local password, it remains a
fallback only until CafeVDB manages or resets the account. Producer credentials
always take precedence. Resetting the account in CafeVDB uses Nextcloud's
credentials and does not reactivate an old Members-local password.

Without those credentials or a legacy Members-local password, Members uses Nextcloud's
`dbhost`, `dbuser` and `dbpassword` as before; that account must be able to
access the views on the MariaDB server. Invalid or unreadable configured
credentials are not silently replaced with the Nextcloud credentials. The
connector account needs the appropriate privileges on the personalized views.

After configuration, run `php occ cafevdbmembers:views:check` from the
Nextcloud directory. The command checks the connection and queries
`PersonalizedMusiciansView` without requiring a member login; a failure points
to the connection settings or view privileges. It does not check every view or
verify that a member's row-access token is valid.
Unexpected check failures log exception types and source locations in the
Nextcloud log, without exception messages or trace arguments. Connection setup
errors are sanitized because underlying exceptions can contain credentials.

## Development

Clone with `git clone --recurse-submodules`, or initialize the pinned build
helpers in an existing checkout with `git submodule update --init --recursive`.
`dev-scripts/lib` is a submodule of `rotdrop/nextcloud-app-build-system`;
update its pinned commit in this repository when adopting upstream changes.

The Makefile requires PHP, Composer, npm and `xpath` (provided by
`libxml-xpath-perl` on Debian/Ubuntu). PHP dependencies are installed with:

```
make dev-setup-php
make phpunit
```

The PHPUnit workflow runs the server suite with Nextcloud backed by SQLite,
MariaDB, and PostgreSQL. The app's fixture databases use a separate temporary
MariaDB server in all three jobs.

`rotdrop/nextcloud-app-toolkit` is installed with the wrapped Composer
dependencies and scoped into `OCA\CAFeVDBMembers\Wrapped` in `vendor-wrapped/`.
Both `vendor/` and `vendor-wrapped/` are required at runtime and included in
release archives. The unscoped dependencies in `build/vendor-wrapped/` are
build inputs, not runtime files. `make distclean` removes both vendor trees.

Run the frontend unit tests independently of Nextcloud with Node.js 24:

```sh
npm ci
npm test
```

`make ts-types-files` uses `rotdrop/php-to-typescript` from its isolated
`vendor-bin/php-to-typescript/` environment. Toolkit modules are generated
under `build/ts-types/php-modules/Wrapped/OCA/RotDrop/Toolkit/`.
Entity metadata generation uses the MariaDB platform selected by
`serverVersion=11.7.0-MariaDB`, without opening an application database
connection. It still requires an installed Nextcloud instance for the console
bootstrap, but does not change its database settings or require a running
MySQL/MariaDB server.

Run `make l10n-sources` before updating POT files and commit the generated
catalogues in `l10n-sources/`. `toolkit-strings.php` exposes the Composer
toolkit's strings to the translation tool, which cannot scan vendor trees.
`vue-components-strings.js` exposes the installed Vue components package's
strings. Neither catalogue is executed, and `l10n-sources/` is excluded from
release archives.

The tests run inside a Nextcloud server checkout, see
`.github/workflows/ci.yml` for the exact setup. CI also runs `make build` with
Node.js 24 to generate the PHP-derived TypeScript modules and build production
frontend assets with Vite before running the test suites.
CI installs JavaScript dependencies with `npm ci` and checks `make realclean`
on both a bare checkout and after the full build and tests, including repeated
cleanup. Cleanup preserves the tracked `package-lock.json`.
CI also runs the frontend Vitest suite independently of the Nextcloud build.

The build regressions can also run without Nextcloud after `make dev-setup-php`
(PHP's `intl` extension is required):

```sh
vendor-bin/phpunit/vendor/bin/phpunit --no-configuration --no-coverage tests/phpunit/Unit/Build/ToolkitBuildTest.php
```

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

Database-backed files appear under the movable `cafevdb/` folder in the
member's Files area. Project participant storage roots are presented as
`cafevdb/Projects/PROJECT_NAME/` before localization; other database storage
identifiers retain their hierarchy beneath `cafevdb/`. The view is read-only
and uses the personalized database views to limit entries to those authorized
for the logged-in member.
Generated folder labels, such as `Projects` and `Finance`, are translatable
through the Members catalogue using the orchestra's configured language.
Project names and database directory-entry names are not translated; neither
is the movable `cafevdb/` mount name.

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

`rotdrop/nextcloud-app-toolkit` is installed with the wrapped Composer
dependencies and scoped into `OCA\CAFeVDBMembers\Wrapped` in `vendor-wrapped/`.
Both `vendor/` and `vendor-wrapped/` are required at runtime and included in
release archives. The unscoped dependencies in `build/vendor-wrapped/` are
build inputs, not runtime files. `make distclean` removes both vendor trees.

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

The build regressions can also run without Nextcloud after `make dev-setup-php`
(PHP's `intl` extension is required):

```sh
vendor-bin/phpunit/vendor/bin/phpunit --no-configuration --no-coverage tests/phpunit/Unit/Build/ToolkitBuildTest.php
```

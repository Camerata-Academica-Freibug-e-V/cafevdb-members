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
- their instrument insurances.

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
Type generation requires a Nextcloud source checkout with its Composer
dependencies, but not an installed Nextcloud instance, database, or app user.
Entity mappings are read using a disconnected, metadata-only entity manager.

Run `make l10n-sources` before updating POT files and commit the generated
catalogues in `l10n-sources/`. `toolkit-strings.php` exposes the Composer
toolkit's strings to the translation tool, which cannot scan vendor trees.
`vue-components-strings.js` exposes the installed Vue components package's
strings. Neither catalogue is executed, and `l10n-sources/` is excluded from
release archives.

The tests run inside a Nextcloud server checkout, see
`.github/workflows/ci.yml` for the exact setup.

The build regressions can also run without Nextcloud after `make dev-setup-php`
(PHP's `intl` extension is required):

```sh
vendor-bin/phpunit/vendor/bin/phpunit --no-configuration --no-coverage tests/phpunit/Unit/Build/ToolkitBuildTest.php
```

The entity type generation regression is skipped when the Nextcloud PHP
autoloaders are unavailable.

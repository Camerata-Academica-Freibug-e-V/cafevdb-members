#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
set -euo pipefail

: "${APP_ID:?APP_ID must be set}"
: "${ARCHIVE:?ARCHIVE must be set}"

tmpdir=$(mktemp -d)
trap 'rm -f "$tmpdir/archive" "$tmpdir/catalogue.php" "$tmpdir/expected" "$tmpdir/actual"; rmdir "$tmpdir"' EXIT

echo "Checking toolkit app discovery"
php -r '
require "vendor/autoload.php";
require "vendor-wrapped/autoload.php";
$service = \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Service\AppInfoService::class;
if (realpath($service::getAppFolderPath()) !== realpath(getcwd())
    || $service::getAppInfoAppName() !== getenv("APP_ID")) {
    fwrite(STDERR, "Toolkit did not discover the consuming app\n");
    exit(1);
}
'

echo "Checking wrapped dependency freshness"
make --no-print-directory --question vendor-wrapped

echo "Checking archive contents"
tar -taf "$ARCHIVE" > "$tmpdir/archive"
for tree in vendor vendor-wrapped; do
  grep -Fqx "$APP_ID/$tree/autoload.php" "$tmpdir/archive" || {
    echo "Missing $tree/autoload.php in archive" >&2
    exit 1
  }
done
for tree in build l10n-sources; do
  if grep -Fq "$APP_ID/$tree/" "$tmpdir/archive" || grep -Fqx "$APP_ID/$tree" "$tmpdir/archive"; then
    echo "Unexpected $tree in archive" >&2
    exit 1
  fi
done

echo "Checking toolkit translation catalogue"
make --no-print-directory -s toolkit-l10n "APP_TOOLKIT_L10N_DEST=$tmpdir/catalogue.php"
awk '/^\$l->t\(/' l10n-sources/toolkit-strings.php | LC_ALL=C sort > "$tmpdir/expected"
awk '/^\$l->t\(/' "$tmpdir/catalogue.php" | LC_ALL=C sort > "$tmpdir/actual"
diff -u "$tmpdir/expected" "$tmpdir/actual"

echo "Checking generated toolkit TypeScript module"
module=php-modules/Wrapped/OCA/RotDrop/Toolkit/Doctrine/ORM.ts
grep -Fq 'export enum EnumOrderByOptions' "build/ts-types/$module"
grep -Fq "build/ts-types/$module" src/toolkit/services/entity-repository.ts

echo "Checking Vite configuration isolation"
npm run test:build-config

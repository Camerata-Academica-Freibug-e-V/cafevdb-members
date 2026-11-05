#!/usr/bin/env php
<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// phpcs:disable PSR1.Files.SideEffects

$appDir = realpath(__DIR__) . '/..';
define('ROT_DROP_DEV_SCRIPTS_APP_DIR', $appDir);

require_once(__DIR__ . '/lib/scripts/console-setup.php');

use Composer\InstalledVersions;

$installedVersions = [
  WrappedInstalledVersions::class => '/vendor-wrapped/composer/InstalledVersions.php',
  InstalledVersions::class => '/vendor/composer/InstalledVersions.php',
];
foreach ($installedVersions as $class => $file) {
  if (!class_exists($class, false) && file_exists($appDir . $file)) {
    include_once $appDir . $file;
  }
}

require_once __DIR__ . "/../vendor/autoload.php";
require_once $appDir . '/vendor-wrapped/autoload.php';

use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Tools\Console\ConsoleRunner;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;

/** @var EntityManager */
$entityManager = \OCP\Server::get(EntityManager::class);

ConsoleRunner::run(new SingleManagerProvider($entityManager));

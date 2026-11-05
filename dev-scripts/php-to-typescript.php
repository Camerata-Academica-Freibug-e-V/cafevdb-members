#!/usr/bin/env php
<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
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

ini_set('display_errors', 'stderr');

$appDir = realpath(__DIR__) . '/..';

try {
  require_once($appDir . '/../../3rdparty/autoload.php');
  require_once($appDir . '/../../lib/composer/autoload.php');
  $appAutoloader = require_once($appDir . '/vendor/autoload.php');
  $appAutoloader->setClassMapAuthoritative(false);
  $appAutoloader->addPsr4('OCA\\CAFeVDBMembers\\', $appDir . '/lib');
  require_once($appDir . '/vendor-wrapped/autoload.php');
  $autoloader = require_once($appDir . '/vendor-bin/php-to-typescript/vendor/autoload.php');
} catch (\Throwable $t) {
  fwrite(STDERR, 'Composer autoloads not set up: ' . $t->getMessage() . PHP_EOL);
  exit(1);
}

use Symfony\Component\Console\Output\ConsoleOutput;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\DriverManager;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\EntityManager;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\ORMSetup;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use OCA\CAFeVDBMembers\Wrapped\Gedmo;
use OCA\RotDrop\DevScripts\PhpToTypeScript;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\DTO\AbstractDTO;
use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\TypeScriptTransformer\TypeReflectors\ClassTypeReflector;
use OCA\RotDrop\DevScripts\PhpToTypeScript\Collectors\DatabaseEntityCollector;
use OCA\RotDrop\DevScripts\PhpToTypeScript\Collectors\DTOCollector;

// store output of different transformers in different files

$excludes = [
  'vendor-wrapped/rotdrop/nextcloud-app-toolkit/src/Service/ArchiveService.php',
  'vendor-wrapped/rotdrop/nextcloud-app-toolkit/src/Backend/ArchiveBackend.php',
  'vendor-wrapped/rotdrop/nextcloud-app-toolkit/src/Backend/ArchiveFormats.php',
];

$scopedNamespaces = [
  \Doctrine::class,
  \Carbon::class,
  \Ramsey\Uuid::class,
];

$phpToTypeScript = new PhpToTypeScript\PhpToTypeScript(
  collectors: [
    DTOCollector::class,
    EnumCollector::class,
    DatabaseEntityCollector::class,
  ],
  metaDataForwarderFolder: 'Wrapped/OCA/RotDrop/Toolkit/Doctrine/ORM',
  entityManagerFactory: function() use ($appDir) {
    $config = ORMSetup::createAttributeMetadataConfiguration([$appDir . '/lib/Database/ORM/Entities']);
    $driverChain = new MappingDriverChain;
    Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM($driverChain);
    $driverChain->addDriver($config->getMetadataDriverImpl(), 'OCA\\CAFeVDBMembers\\Database\\ORM\\Entities');
    $config->setMetadataDriverImpl($driverChain);
    $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
    // Pin the platform so reading metadata never connects to discover the server version.
    $connection = DriverManager::getConnection([
      'driver' => 'pdo_mysql',
      'serverVersion' => '12.0.0-MariaDB',
    ]);
    return new EntityManager($connection, $config);
  },
  devScriptsFolder: __DIR__,
  excludes: $excludes,
  scopedNamespaces: $scopedNamespaces,
);

try {
  $phpToTypeScript->run(
    input: new \Symfony\Component\Console\Input\ArgvInput,
    output: new ConsoleOutput,
  );
} catch (Throwable $t) {
  fwrite(STDERR, 'Type generation failed: ' . $t->getMessage() . PHP_EOL . print_r($t->getTrace(), true) . PHP_EOL);
  exit(1);
}

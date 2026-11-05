<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Database;

use UnexpectedValueException;

use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\EntityManagerInterface;

use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;

use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager;

/** Register some utiltiy services in order to ease dependency injection. */
class Registration
{
  /**
   * Static service registration routine called by \OCA\CAFEVDB\AppInfo\Application.
   *
   * @param IRegistrationContext $context
   *
   * @return void
   */
  public static function register(IRegistrationContext $context):void
  {
    $context->registerServiceAlias(EntityManagerInterface::class, EntityManager::class);
    $context->registerServiceAlias(AbstractEntityManager::class, EntityManager::class);

    /* Doctrine DBAL needs a factory to be constructed. */
    $context->registerService(Connection::class, function(ContainerInterface $c) {
      return $c->get(EntityManagerInterface::class)->getConnection();
    });
  }
}

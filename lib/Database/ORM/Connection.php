<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\CAFeVDBMembers\Database\ORM;

use Closure;
use SensitiveParameter;

use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Configuration;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Connection as DBALConnection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Driver;

/**
 * Simple wrapper which resurrect the post-connect hook of DBAL v3, sort of.
 */
class Connection extends DBALConnection
{
  public const POST_CONNECT_KEY = 'postConnect';

  protected ?Closure $postConnectHook = null;

  /** {@inheritdoc} */
  public function __construct(
    #[SensitiveParameter]
    array $params,
    protected Driver $driver,
    ?Configuration $config = null,
  ) {
    $this->postConnectHook = $params[self::POST_CONNECT_KEY] ?? null;
    unset($params[self::POST_CONNECT_KEY]);
    parent::__construct($params, $driver, $config);
  }

  /**
   * @param ?Closure $postConnectHook
   *
   * @return self
   */
  public function setPostConnectHook(?Closure $postConnectHook): self
  {
    $this->postConnectHook = $postConnectHook;

    return $this;
  }

  /** {@inheritdoc} */
  protected function connect(): Driver\Connection
  {
    $alreadyConnected = $this->isConnected();
    $driverConnection = parent::connect();
    if (!$alreadyConnected && $this->postConnectHook) {
      call_user_func($this->postConnectHook, $this);
    }
    return $driverConnection;
  }
}

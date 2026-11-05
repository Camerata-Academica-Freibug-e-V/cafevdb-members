<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022, 2025, 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Database\ORM\Entities;

use OCA\CAFeVDBMembers\Database\DBAL\Types;
use OCA\CAFeVDBMembers\Database\ORM as CAFEVDB;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\ArrayCollection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\Common\Collections\Collection;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\Mapping as ORM;
use OCA\CAFeVDBMembers\Wrapped\MediaMonks\Doctrine\Mapping as MediaMonks;

/**
 * EncryptedFileData
 */
#[ORM\Entity]
class EncryptedFileData extends FileData
{
  /**
   * @var EncryptedFile
   *
   * {@inheritdoc}
   */
  protected $file;

  /**
   * @var string
   */
  #[MediaMonks\Transformable(name: 'encrypt', override: true, context: 'encryptionContext')]
  protected $data;

  /**
   * @var array
   *
   * In memory encryption context to support multi user encryption.
   */
  protected $encryptionContext;

  /**
   * Add a user-id or group-id to the list of "encryption identities",
   * i.e. the list of identities which can read and write this entry.
   *
   * @param string $personality
   *
   * @return EncryptedFileData
   */
  public function addEncryptionIdentity(string $personality):EncryptedFileData
  {
    if (empty($this->encryptionContext)) {
      $this->encryptionContext = [];
    }
    if (!in_array($personality, $this->encryptionContext)) {
      $this->encryptionContext[] = $personality;
    }
    return $this;
  }

  /**
   * Remove a user-id or group-id to the list of "encryption identities",
   * i.e. the list of identities which can read and write this entry.
   *
   * @param string $personality
   *
   * @return EncryptedFileData
   */
  public function removeEncryptionIdentity(string $personality):EncryptedFileData
  {
    $pos = array_search($personality, $this->encryptionContext??[]);
    if ($pos !== false) {
      unset($this->encryptionContext[pos]);
      $this->encryptionContext = array_values($this->encryptionContext);
    }
    return $this;
  }
}

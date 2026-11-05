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

namespace OCA\CAFeVDBMembers\Crypto;

use OCA\CAFeVDBMembers\Exceptions;

/**
 * Subset of the key- and seal-services of the orchestra app which is needed
 * by this app.
 */
interface IEncryptionService
{
  /**
   * Initialize the encryption key-pair of the current user.
   *
   * @return void
   *
   * @throws Exceptions\EncryptionException
   */
  public function initEncryptionKeyPair():void;

  /**
   * Loose check if the given data may be sealed data.
   *
   * @param null|string $data
   *
   * @return bool
   */
  public function isSealedData(?string $data):bool;

  /**
   * @param string $sealedData
   *
   * @return array The ids of the users and groups the data is sealed for.
   *
   * @throws Exceptions\EncryptionException
   */
  public function getSealOwners(string $sealedData):array;

  /**
   * Seal the data for those of the given owners which are able to encrypt.
   *
   * @param null|string $data
   *
   * @param array $ownerIds
   *
   * @return null|string
   *
   * @throws Exceptions\EncryptionException
   */
  public function encrypt(?string $data, array $ownerIds):?string;

  /**
   * Unseal the data with the first of the given owners which is able to
   * decrypt.
   *
   * @param null|string $data
   *
   * @param array $ownerIds
   *
   * @return null|string
   *
   * @throws Exceptions\EncryptionException
   */
  public function decrypt(?string $data, array $ownerIds):?string;

  /**
   * @param string $ownerId
   *
   * @param string $key
   *
   * @return null|string
   *
   * @throws Exceptions\CannotDecryptException
   */
  public function getSharedPrivateValue(string $ownerId, string $key):?string;
}

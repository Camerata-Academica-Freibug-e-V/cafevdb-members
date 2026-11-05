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

use Psr\Container\ContainerInterface;
use Throwable;

use OCA\CAFeVDBMembers\Exceptions;

/**
 * Adapter for the crypto services of the orchestra app. The orchestra app
 * classes are resolved lazily by name, so that this app and its tests work
 * without the orchestra app being installed.
 */
class OrchestraEncryptionService implements IEncryptionService
{
  private const KEY_SERVICE_CLASS = 'OCA\\CAFEVDB\\Crypto\\AsymmetricKeyService';
  private const SEAL_CRYPTOR_CLASS = 'OCA\\CAFEVDB\\Crypto\\SealCryptor';
  private const ORCHESTRA_EXCEPTIONS = 'OCA\\CAFEVDB\\Exceptions\\';

  private ?object $keyService = null;

  private ?object $sealCryptor = null;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private ContainerInterface $appContainer,
  ) {
  }
  // phpcs:enable

  /**
   * @param string $class
   *
   * @return object
   *
   * @throws Exceptions\EncryptionException
   */
  private function orchestraService(string $class):object
  {
    try {
      return $this->appContainer->get($class);
    } catch (Throwable $t) {
      throw new Exceptions\EncryptionException('The orchestra app services are not available.', 0, $t);
    }
  }

  /** @return object */
  private function keyService():object
  {
    return $this->keyService ??= $this->orchestraService(self::KEY_SERVICE_CLASS);
  }

  /** @return object */
  private function sealCryptor():object
  {
    return $this->sealCryptor ??= $this->orchestraService(self::SEAL_CRYPTOR_CLASS);
  }

  /**
   * Map exceptions of the orchestra app to the exceptions of this app.
   *
   * @param Throwable $t
   *
   * @return Throwable
   */
  private static function translateException(Throwable $t):Throwable
  {
    if ($t instanceof Exceptions\EncryptionException) {
      return $t;
    }
    $map = [
      'CannotDecryptException' => Exceptions\CannotDecryptException::class,
      'EncryptionFailedException' => Exceptions\EncryptionFailedException::class,
      'DecryptionFailedException' => Exceptions\EncryptionFailedException::class,
      'EncryptionException' => Exceptions\EncryptionException::class,
    ];
    foreach ($map as $orchestraClass => $localClass) {
      if (is_a($t, self::ORCHESTRA_EXCEPTIONS . $orchestraClass)) {
        return new $localClass($t->getMessage(), $t->getCode(), $t);
      }
    }
    return $t;
  }

  /** {@inheritdoc} */
  public function initEncryptionKeyPair():void
  {
    try {
      $this->keyService()->initEncryptionKeyPair();
    } catch (Throwable $t) {
      $t = self::translateException($t);
      if (!($t instanceof Exceptions\EncryptionException)) {
        // e.g. missing login credentials in CLI or background jobs
        $t = new Exceptions\EncryptionException($t->getMessage(), $t->getCode(), $t);
      }
      throw $t;
    }
  }

  /** {@inheritdoc} */
  public function isSealedData(?string $data):bool
  {
    // same loose format check as the orchestra app
    if (strlen($data ?? '') < 10) {
      return false;
    }
    if ($data[8] != '|') {
      return false;
    }
    $length = (int)substr($data, 0, 8);
    return strlen($data) >= (10 + $length) && $data[8 + 1 + $length] == '|';
  }

  /** {@inheritdoc} */
  public function getSealOwners(string $sealedData):array
  {
    try {
      $sealService = $this->sealCryptor()->getSealService();
      return array_keys($sealService->parseSeal($sealedData)['keys']);
    } catch (Throwable $t) {
      throw self::translateException($t);
    }
  }

  /**
   * @param string $method Either "encrypt" or "decrypt".
   *
   * @param null|string $data
   *
   * @param array $ownerIds
   *
   * @return null|string
   */
  private function sealOperation(string $method, ?string $data, array $ownerIds):?string
  {
    try {
      $sealCryptors = [];
      foreach ($ownerIds as $ownerId) {
        $cryptor = $this->keyService()->getCryptor($ownerId);
        $capable = $method == 'encrypt' ? $cryptor->canEncrypt() : $cryptor->canDecrypt();
        if ($capable) {
          $sealCryptors[$ownerId] = $cryptor;
        }
      }
      $sealCryptor = $this->sealCryptor();
      $sealCryptor->setSealCryptors($sealCryptors);
      return $sealCryptor->$method($data);
    } catch (Throwable $t) {
      throw self::translateException($t);
    }
  }

  /** {@inheritdoc} */
  public function encrypt(?string $data, array $ownerIds):?string
  {
    return $this->sealOperation('encrypt', $data, $ownerIds);
  }

  /** {@inheritdoc} */
  public function decrypt(?string $data, array $ownerIds):?string
  {
    return $this->sealOperation('decrypt', $data, $ownerIds);
  }

  /** {@inheritdoc} */
  public function getSharedPrivateValue(string $ownerId, string $key):?string
  {
    try {
      return $this->keyService()->getSharedPrivateValue($ownerId, $key);
    } catch (Throwable $t) {
      throw self::translateException($t);
    }
  }
}

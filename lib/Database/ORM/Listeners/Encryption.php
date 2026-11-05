<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2024, 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Database\ORM\Listeners;

use RuntimeException;

use OCP\IUserSession;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\Crypto\IEncryptionService;
use OCA\CAFeVDBMembers\Exceptions\EncryptionException;
use OCA\CAFeVDBMembers\Wrapped\MediaMonks\Doctrine\Transformable;

/** Implement transparent encryption/decryption of database fields. */
class Encryption implements Transformable\Transformer\TransformerInterface
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private IUserSession $userSession,
    private IEncryptionService $encryptionService,
    protected LoggerInterface $logger,
  ) {
    try {
      $this->encryptionService->initEncryptionKeyPair();
    } catch (EncryptionException $e) {
      $this->logDebug('Unable to initialize on-the-fly encryption');
    }
  }
  // phpcs:enable

  /** {@inheritdoc} */
  public function isCachable(): bool
  {
    return true;
  }

  /**
   * {@inheritdoc}
   *
   * Forward transform to data-base (encrypt).
   *
   * @param string $value Unencrypted data.
   *
   * @return string Encrypted data.
   */
  public function transform(?string $value, mixed &$context = null): mixed
  {
    $context = $this->manageEncryptionContext(null, $context);

    if (empty($context)) {
      return $value;
    }

    return $this->encryptionService->encrypt($value, $context);
  }

  /**
   * {@inheritdoc}
   *
   * Decrypt.
   *
   * @param string $value Encrypted data.
   *
   * @return string Decrypted data.
   */
  public function reverseTransform(?string $value, mixed &$context = null): mixed
  {
    if (!$this->encryptionService->isSealedData($value)) {
      return $value;
    }

    $context = $this->manageEncryptionContext($value, $context);

    if (empty($context)) {
      return $value;
    }

    return $this->encryptionService->decrypt($value, $context);
  }

  /**
   * @param null|string $value
   *
   * @param mixed $context
   *
   * @return array
   */
  private function manageEncryptionContext(?string $value, mixed $context):array
  {
    if (is_string($context)) {
      try {
        $context = json_decode($context);
      } catch (\Throwable $t) {
        $context = explode(',', $context);
      }
    }

    if (empty($context)) {
      $context = [];
    }

    if ($this->encryptionService->isSealedData($value)) {
      $context = array_merge($context, $this->encryptionService->getSealOwners($value));
    }
    $user = $this->userSession->getUser();
    if (!empty($user)) {
      $userId = $user->getUID();
      if (array_search($userId, $context) === false) {
        $context[] = $userId;
      }
    }

    if (!is_array($context)) {
      throw new RuntimeException('Encryption context must be an array of user- or @group-ids.');
    }
    return array_unique($context);
  }
}

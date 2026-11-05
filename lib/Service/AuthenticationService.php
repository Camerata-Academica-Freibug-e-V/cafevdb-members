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

namespace OCA\CAFeVDBMembers\Service;

use Psr\Log\LoggerInterface;
use OCP\IL10N;

use OCA\CAFeVDBMembers\Crypto\IEncryptionService;

use OCA\CAFeVDBMembers\Exceptions;

/**
 * Some support functions for giving access to the private data of a member.
 */
class AuthenticationService
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\UtilTrait;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private ?string $userId,
    private IEncryptionService $encryptionService,
    protected LoggerInterface $logger,
    protected IL10N $l,
  ) {
  }
  // phpcs:enable

  /**
   * Try to get and decrypt the row-access-token from the config space. The
   * row access token is the authentication token to get the respective person
   * access to the data of the cafevdb database. It is encrypted with the
   * user's public key, in order to get access it has to be decrypted and a
   * hash of it has to be passed to the database server.
   *
   * Here we fetch the token, decrypt and hash it.
   *
   * @return string The hashed row-access token.
   *
   * @throws Exceptions\AuthenticationException
   */
  public function getRowAccessToken(): string
  {
    if (empty($this->userId)) {
      throw new Exceptions\NotLoggedInException($this->l->t('Empty user id, no one seems to be logged in at the moment.'));
    }
    try {
      $rowAccessToken = $this->encryptionService->getSharedPrivateValue($this->userId, 'rowAccessToken');
    } catch (Exceptions\CannotDecryptException $e) {
      throw new Exceptions\RowAccessTokenInvalidException(
        $this->l->t(
          'The row-access token for "%s" is present but invalid, the user\'s data will be unaccessble.',
          $this->userId
        ),
        $e->getCode(),
        $e);
    }
    if (empty($rowAccessToken)) {
      throw new Exceptions\RowAccessTokenMissingException($this->l->t('The row-access token for "%s" is missing, the user\'s data will be unaccessble.', $this->userId));
    }
    $rowAccessTokenHash = \hash('sha512', $rowAccessToken);

    return $rowAccessTokenHash;
  }
}

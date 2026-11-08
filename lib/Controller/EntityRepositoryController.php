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

namespace OCA\CAFeVDBMembers\Controller;

use Spatie\TypeScriptTransformer\Attributes as TSAttributes;

use OCP\AppFramework\Http\Attribute as CoreAttributes;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\Exceptions\AuthenticationException;
use OCA\CAFeVDBMembers\Service\AuthenticationService;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer\EntitySerializer;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager;

/** Export entities to the frontend. */
#[TSAttributes\TypeScript]
class EntityRepositoryController extends EntityRepositoryControllerBase
{
  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    ?string $appName,
    IRequest $request,
    AbstractEntityManager $entityManager,
    EntitySerializer $entitySerializer,
    IL10N $l,
    LoggerInterface $logger,
    private AuthenticationService $authenticationService,
  ) {
    parent::__construct($appName, $request, $entityManager, $entitySerializer, $l, $logger);
  }
  // phpcs:enable

  /** {@inheritdoc} */
  #[CoreAttributes\NoAdminRequired]
  #[CoreAttributes\ApiRoute(
    verb: 'GET',
    url: '/' . self::END_POINT . '/{entityName}/{depth}',
    defaults: ['depth' => 0],
  )]
  public function getEntities(
    string $entityName,
    ?string $find = null,
    ?string $findBy = null,
    ?string $sortBy = null,
    ?int $limit = null,
    int $offset = 0,
    int $depth = 0,
  ): DataResponse {
    try {
      $this->authenticationService->getRowAccessToken();
    } catch (AuthenticationException $t) {
      throw new OCS\OCSForbiddenException(
        $this->l->t('Access to the member-data is not authorized: %s', $t->getMessage()),
        previous: $t,
      );
    }

    return parent::getEntities($entityName, $find, $findBy, $sortBy, $limit, $offset, $depth);
  }
}

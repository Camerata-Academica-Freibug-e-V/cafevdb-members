<?php
/**
 * Member's data base connector for CAFEVDB orchestra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Controller;

use OCP\AppFramework\OCSController;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer\EntitySerializer;

/**
 * Keep the trait on a base class: an attributed method alias would register
 * another route even if the alias were private.
 */
abstract class EntityRepositoryControllerBase extends OCSController
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Controller\EntityRepositoryControllerTrait;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    ?string $appName,
    IRequest $request,
    private AbstractEntityManager $entityManager,
    private EntitySerializer $entitySerializer,
    protected IL10N $l,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($appName, $request);
  }
  // phpcs:enable
}

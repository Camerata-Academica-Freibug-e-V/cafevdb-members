<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Controller;

use ReflectionClass;

use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\Controller\EntityRepositoryController;
use OCA\CAFeVDBMembers\Controller\EntityRepositoryControllerBase;
use OCA\CAFeVDBMembers\Exceptions\RowAccessTokenMissingException;
use OCA\CAFeVDBMembers\Service\AuthenticationService;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\ORM\EntityRepository;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\AbstractEntityManager;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer\EntityResponse;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer\EntitySerializer;

/** Authorization and routing of the shared entity endpoint. */
#[Attributes\CoversClass(EntityRepositoryController::class)]
#[Attributes\UsesClass(EntityRepositoryControllerBase::class)]
#[Attributes\UsesTrait(\OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Controller\EntityRepositoryControllerTrait::class)]
#[Attributes\UsesClass(EntityResponse::class)]
class EntityRepositoryControllerTest extends TestCase
{
  /** @return void */
  public function testDeniedAccessDoesNotTouchTheRepositoryOrSerializer(): void
  {
    $entityManager = $this->createMock(AbstractEntityManager::class);
    $entityManager->expects($this->never())->method('getRepository');
    $serializer = $this->createMock(EntitySerializer::class);
    $serializer->expects($this->never())->method('reset');
    $authentication = $this->createMock(AuthenticationService::class);
    $failure = new RowAccessTokenMissingException('Missing token');
    $authentication->expects($this->once())->method('getRowAccessToken')->willThrowException($failure);
    $l = $this->createStub(IL10N::class);
    $l->method('t')->willReturn('Access denied');
    $controller = new EntityRepositoryController(
      'cafevdbmembers',
      $this->createStub(IRequest::class),
      $entityManager,
      $serializer,
      $l,
      $this->createStub(LoggerInterface::class),
      $authentication,
    );

    try {
      $controller->getEntities('Musician', findBy: base64_encode('[]'));
      $this->fail('A missing token must deny access.');
    } catch (OCSForbiddenException $e) {
      $this->assertSame($failure, $e->getPrevious());
      $this->assertSame('Access denied', $e->getMessage());
    }
  }

  /** @return void */
  public function testAuthorizedAccessUsesTheSharedRepositoryEndpoint(): void
  {
    $authentication = $this->createMock(AuthenticationService::class);
    $authentication->expects($this->once())->method('getRowAccessToken')->willReturn('hashed-token');
    $repository = $this->createMock(EntityRepository::class);
    $repository->expects($this->once())->method('findBy')->with(['id' => 42], null, 3, 1)->willReturn([]);
    $entityManager = $this->createMock(AbstractEntityManager::class);
    $entityManager->method('getEntityNamespace')->willReturn('MemberEntities');
    $entityManager->expects($this->once())->method('getRepository')->with('MemberEntities\\Musician')->willReturn($repository);
    $serializer = $this->createMock(EntitySerializer::class);
    $serializer->expects($this->once())->method('reset');
    $serializer->expects($this->once())->method('setCommonPrefix')->with('MemberEntities');
    $export = new EntityResponse([], []);
    $serializer->expects($this->once())->method('export')->willReturn($export);
    $controller = new EntityRepositoryController(
      'cafevdbmembers',
      $this->createStub(IRequest::class),
      $entityManager,
      $serializer,
      $this->createStub(IL10N::class),
      $this->createStub(LoggerInterface::class),
      $authentication,
    );

    $response = $controller->getEntities('Musician', findBy: base64_encode('{"id":42}'), limit: 3, offset: 1, depth: 2);
    $this->assertSame($export, $response->getData());
  }

  /** @return void */
  public function testOnlyTheGuardedMethodAdvertisesAnEntityRoute(): void
  {
    $routes = [];
    foreach ((new ReflectionClass(EntityRepositoryController::class))->getMethods() as $method) {
      foreach ($method->getAttributes(ApiRoute::class) as $attribute) {
        $routes[$method->getName()] = $attribute->getArguments();
      }
    }
    $this->assertSame(['getEntities'], array_keys($routes));
    $this->assertSame('/v1/entities/{entityName}/{depth}', $routes['getEntities']['url']);
  }
}

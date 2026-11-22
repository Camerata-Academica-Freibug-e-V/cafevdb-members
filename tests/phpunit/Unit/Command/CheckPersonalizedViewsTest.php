<?php
/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Tests\Unit\Command;

use PHPUnit\Framework\Attributes;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use OCA\CAFeVDBMembers\Command\CheckPersonalizedViews;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;

/** Check command output and credential-safe diagnostics. */
#[Attributes\CoversClass(CheckPersonalizedViews::class)]
class CheckPersonalizedViewsTest extends TestCase
{
  /** @return void */
  public function testUnexpectedFailureLogsOnlyDiagnosticLocations(): void
  {
    $cause = new \TypeError('connector-secret');
    $failure = new \RuntimeException('connector-user connector-password', 0, $cause);
    $container = $this->createStub(ContainerInterface::class);
    $container->method('get')->willThrowException($failure);
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('error')->with(
      'Personalized views check failed unexpectedly.',
      [
        'app' => 'cafevdbmembers',
        'causes' => [
          ['type' => \RuntimeException::class, 'file' => $failure->getFile(), 'line' => $failure->getLine()],
          ['type' => \TypeError::class, 'file' => $cause->getFile(), 'line' => $cause->getLine()],
        ],
      ],
    );
    $tester = new CommandTester(new CheckPersonalizedViews('cafevdbmembers', $container, $logger));
    self::assertSame(Command::FAILURE, $tester->execute([]));
    self::assertStringContainsString('consult the Nextcloud log', $tester->getDisplay());
    self::assertStringNotContainsString('connector-secret', $tester->getDisplay());
    self::assertStringNotContainsString('connector-password', $tester->getDisplay());
    self::assertStringNotContainsString('connector-user', $tester->getDisplay());
  }

  /**
   * @param null|string $error
   * @param int $status
   *
   * @return void
   */
  #[Attributes\DataProvider('validationResults')]
  public function testValidationResult(?string $error, int $status): void
  {
    $manager = $this->createStub(EntityManager::class);
    $manager->method('validatePersonalizedViews')->willReturn($error);
    $container = $this->createStub(ContainerInterface::class);
    $container->method('get')->willReturn($manager);
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->never())->method('error');
    $tester = new CommandTester(new CheckPersonalizedViews('cafevdbmembers', $container, $logger));
    self::assertSame($status, $tester->execute([]));
    self::assertStringContainsString($error ?? 'The personalized views database is accessible.', $tester->getDisplay());
  }

  /** @return iterable */
  public static function validationResults(): iterable
  {
    yield 'accessible' => [null, Command::SUCCESS];
    yield 'expected failure' => ['Check the view privileges.', Command::FAILURE];
  }
}

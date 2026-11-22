<?php
/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Command;

use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Check the configured personalized views connection. */
class CheckPersonalizedViews extends Command
{
  /** {@inheritdoc} */
  public function __construct(
    private string $appName,
    private ContainerInterface $appContainer,
    private LoggerInterface $logger,
  ) {
    parent::__construct();
  }

  /** {@inheritdoc} */
  protected function configure()
  {
    $this->setName($this->appName . ':views:check')
      ->setDescription('Check access to the personalized views database');
  }

  /** {@inheritdoc} */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    try {
      $error = $this->appContainer->get(EntityManager::class)->validatePersonalizedViews();
    } catch (\Throwable $exception) {
      // Do not log exception messages, arguments or traces: they may contain credentials.
      $causes = [];
      do {
        $causes[] = [
          'type' => get_class($exception),
          'file' => $exception->getFile(),
          'line' => $exception->getLine(),
        ];
        $exception = $exception->getPrevious();
      } while ($exception !== null);
      $this->logger->error('Personalized views check failed unexpectedly.', [
        'app' => $this->appName,
        'causes' => $causes,
      ]);
      $error = 'Unable to complete the personalized views check. Check the connector settings and database name, and consult the Nextcloud log for diagnostic locations.';
    }
    if ($error !== null) {
      $output->writeln('<error>' . $error . '</error>');
      return self::FAILURE;
    }
    $output->writeln('The personalized views database is accessible.');
    return self::SUCCESS;
  }
}

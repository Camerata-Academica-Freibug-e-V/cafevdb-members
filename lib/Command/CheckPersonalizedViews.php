<?php
/**
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Command;

use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** Check the configured personalized views connection. */
class CheckPersonalizedViews extends Command
{
  /** {@inheritdoc} */
  public function __construct(private string $appName, private ContainerInterface $appContainer)
  {
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
      $error = 'Unable to configure the personalized views database. Check the connector settings and database name.';
    }
    if ($error !== null) {
      $output->writeln('<error>' . $error . '</error>');
      return self::FAILURE;
    }
    $output->writeln('The personalized views database is accessible.');
    return self::SUCCESS;
  }
}

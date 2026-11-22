<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Service;

use JsonException;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Security\ICrypto;
use RuntimeException;
use Throwable;

use OCA\CAFeVDBMembers\AppInfo\Application;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;

/** Resolve producer-owned metadata without copying connection credentials. */
class ConnectorConfiguration
{
  /** {@inheritdoc} */
  public function __construct(
    private IConfig $config,
    private IL10N $l,
    private IAppManager $appManager,
    private string $appName,
    private ICrypto $crypto,
  ) {
  }

  /** @param null|string $password Password for the producer-published account. */
  public function saveViewsDatabasePassword(#[\SensitiveParameter] ?string $password): void
  {
    if ($password === null) {
      $this->config->deleteAppValue($this->appName, ConfigConstants::VIEWS_DATABASE_PASSWORD_KEY);
      return;
    }
    if ($password === '') {
      throw new RuntimeException('The views database password must not be empty.');
    }
    $account = $this->resolve()['descriptor']['account'] ?? null;
    if (!is_array($account) || !is_string($account['user'] ?? null) || $account['user'] === '') {
      throw new RuntimeException('CafeVDB connector account metadata is unavailable.');
    }
    $this->config->setAppValue(
      $this->appName,
      ConfigConstants::VIEWS_DATABASE_PASSWORD_KEY,
      $this->crypto->encrypt($password),
    );
  }

  /** @return null|string Decrypted locally stored password, or null when unset. */
  public function viewsDatabasePassword(): ?string
  {
    $encrypted = $this->config->getAppValue($this->appName, ConfigConstants::VIEWS_DATABASE_PASSWORD_KEY, '');
    if ($encrypted === '') {
      return null;
    }
    try {
      return $this->crypto->decrypt($encrypted);
    } catch (Throwable $exception) {
      throw new RuntimeException('Unable to decrypt the views database password.', 0, $exception);
    }
  }

  /** @return bool Whether a local password is configured. */
  public function hasViewsDatabasePassword(): bool
  {
    return $this->config->getAppValue($this->appName, ConfigConstants::VIEWS_DATABASE_PASSWORD_KEY, '') !== '';
  }

  /** @return bool Whether the views database is inherited. */
  public function inheritsDatabase(): bool
  {
    $default = $this->config->getAppValue($this->appName, ConfigConstants::USER_VIEWS_DATABASE_KEY, '') === '' ? 'on' : 'off';
    return $this->config->getAppValue($this->appName, ConfigConstants::INHERIT_VIEWS_DATABASE_KEY, $default) === 'on';
  }

  /** @return array Effective metadata and actionable compatibility warnings. */
  public function resolve(): array
  {
    $database = $this->config->getAppValue($this->appName, ConfigConstants::USER_VIEWS_DATABASE_KEY, '');
    $inherited = $this->inheritsDatabase();
    $warnings = [];
    $descriptor = $this->descriptor();
    if ($descriptor === null) {
      $warnings[] = $this->l->t(
        'CafeVDB connector metadata is unavailable or unsupported. The existing members database setting is retained.'
        . ' Open CafeVDB sharing settings to publish its connector configuration.',
      );
    } else {
      if ($inherited) {
        $database = $descriptor['database'];
      } elseif ($database !== $descriptor['database']) {
        $warnings[] = $this->l->t('The members database override differs from the CafeVDB views database.');
      }
      if (!$descriptor['personalizedViewsEnabled']) {
        $warnings[] = $this->l->t('Personalized views are disabled in CafeVDB.');
      }
    }
    if ($database === '') {
      $warnings[] = $this->l->t('No personalized views database is available. Configure CafeVDB or provide a members database override.');
    }
    return [
      'database' => $database,
      'inherited' => $inherited,
      'descriptor' => $descriptor,
      'warnings' => $warnings,
    ];
  }

  /** @return null|array Validated version-one metadata; never credentials. */
  private function descriptor(): ?array
  {
    $producer = Application::getOrchestraAppName();
    if (!$this->appManager->isInstalled($producer)
        || $this->config->getAppValue($producer, 'enabled', 'no') === 'no') {
      return null;
    }
    $value = $this->config->getAppValue($producer, ConfigConstants::CONNECTOR_DESCRIPTOR_KEY, '');
    if ($value === '') {
      return null;
    }
    try {
      $descriptor = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException) {
      return null;
    }
    if (!is_array($descriptor) || ($descriptor['version'] ?? null) !== 1
        || !is_string($descriptor['database'] ?? null) || $descriptor['database'] === ''
        || !is_bool($descriptor['personalizedViewsEnabled'] ?? null)
        || !is_string($descriptor['revision'] ?? null)
        || !is_array($descriptor['account'] ?? null)
        || !is_string($descriptor['account']['user'] ?? null)
        || !is_string($descriptor['account']['accountHost'] ?? null)) {
      return null;
    }
    $descriptor['account'] = array_intersect_key($descriptor['account'], array_flip(['user', 'accountHost']));
    return array_intersect_key($descriptor, array_flip(['version', 'database', 'account', 'personalizedViewsEnabled', 'revision']));
  }

  /**
   * @param array $status Resolved connector information.
   * @param null|string $actualAccount Account returned by CURRENT_USER().
   *
   * @return array Status including account verification.
   */
  public function compareAccount(array $status, ?string $actualAccount): array
  {
    $status['actualAccount'] = $actualAccount;
    if ($actualAccount === null) {
      $status['warnings'][] = $this->l->t('Consumer account comparison is unverified because the members connection could not be checked.');
      return $status;
    }
    $account = $status['descriptor']['account'] ?? null;
    if ($account !== null && $actualAccount !== $account['user'] . '@' . $account['accountHost']) {
      $status['warnings'][] = $this->l->t('The connected MariaDB account differs from the account granted access by CafeVDB. Check its username and account host.');
    }
    return $status;
  }
}

<?php
/**
 * SPDX-FileCopyrightText: 2026 Claus-Justus Heine
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute as CoreAttributes;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

use OCA\CAFeVDBMembers\Storage\Database\ScanService;

/** Authenticated trigger for the current user's database-storage scan. */
class DatabaseStorageScanController extends Controller
{
  public function __construct(
    string $appName,
    IRequest $request,
    private ScanService $scanner,
    private LoggerInterface $logger,
  ) {
    parent::__construct($appName, $request);
  }

  #[CoreAttributes\NoAdminRequired]
  #[CoreAttributes\FrontpageRoute(verb: 'POST', url: '/database-storage/scan')]
  public function scan(): DataResponse
  {
    try {
      $status = $this->scanner->scan();
      return new DataResponse(['status' => $status], $status === 'unavailable' ? Http::STATUS_SERVICE_UNAVAILABLE : Http::STATUS_OK);
    } catch (Throwable $e) {
      $this->logger->error('Authenticated database-storage scan failed.', ['exception' => $e]);
      return new DataResponse(['status' => 'error'], Http::STATUS_SERVICE_UNAVAILABLE);
    }
  }
}

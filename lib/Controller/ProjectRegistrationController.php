<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2023-2026 Claus-Justus Heine
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

use DateTimeImmutable;
use DateTimeInterface;
use DateTime;

use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCP\AppFramework\AuthPublicShareController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute as CoreAttributes;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Http\Template\SimpleMenuAction;
use OCP\AppFramework\Services\IInitialState;
use OCP\Constants as CoreConstants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IAppConfig;
use OCP\IDateTimeZone;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\IShare;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\ISecureRandom;
use OCP\Security\PasswordContext;
use OCP\Util;
use Psr\Log\LoggerInterface;


use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumParticipantFieldDataType as FieldDataType;
use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumParticipantFieldMultiplicity as FieldMultiplicity;
use OCA\CAFeVDBMembers\Constants;
use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Model\ApplicationShare;
use OCA\CAFeVDBMembers\Service\AssetService;
use OCA\CAFeVDBMembers\Service\EventsService;
use OCA\CAFeVDBMembers\Service\ProjectRegistrationService;

/** AJAX endpoints for a project registration form. */
class ProjectRegistrationController extends AuthPublicShareController
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\ResponseTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\DateTimeTrait;

  /** @var ApplicationShare */
  private ?ApplicationShare $share = null;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $appName,
    IRequest $request,
    ISession $session,
    IURLGenerator $urlGenerator,
    private AssetService $assetService,
    private EntityManager $entityManager,
    private EventsService $eventsService,
    private IAppConfig $appConfig,
    private IConfig $cloudConfig,
    private IDateTimeZone $dateTimeZone,
    private IEventDispatcher $eventDispatcher,
    private IInitialState $initialState,
    private ISecureRandom $secureRandom,
    private IUserSession $userSession,
    private ProjectRegistrationService $registrationService,
    protected IL10N $l,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($appName, $request, $session, $urlGenerator);
  }
  // phpcs:enable

  /**
   * @return TemplateResponse
   *
   * @todo Check whether we do want CSRF.
   */
  #[CoreAttributes\NoAdminRequired]
  #[CoreAttributes\NoCSRFRequired]
  #[CoreAttributes\PublicPage]
  #[CoreAttributes\FrontpageRoute(
    // Registration with given project and section, optional token
    postfix: '_pages',
    url: '/registration/{section}/{token}',
    verb: 'GET',
    defaults: [
      'token' => Constants::NEW_APPLICATION_TOKEN,
    ],
    requirements: [
      'section' => '[a-z-]+',
      'token' => (
        '^('
        // plain project name, needs to be remapped from token to projec name
        . Constants::TEMPORARY_PROJECT_NAME_REGEXP
        . '|'
        // composite project name and token, this is required as the
        // URLGenerator call in the public-share framework just allows one
        // parameter for the token and we decided to use the project-name +
        // some fancy hash
        . Constants::TEMPORARY_PROJECT_NAME_REGEXP . '/' . Constants::PROJECT_APPLICATION_TOKEN_REGEXP
        . ')$'
      ),
    ],
  )]
  #[CoreAttributes\FrontpageRoute(
    // Registration home with optional project and optional token
    url: '/registration/{token}',
    verb: 'GET',
    requirements: [
      'token' => (
        '^('
        // empty is ok
        . '|'
        // plain project name, needs to be remapped
        . Constants::TEMPORARY_PROJECT_NAME_REGEXP
        . '|'
        // composite project name and token, this is required as the
        // URLGenerator call in the public-share framework just allows one
        // parameter for the token and we decided to use the project-name +
        // some fancy hash
        . Constants::TEMPORARY_PROJECT_NAME_REGEXP . '/' . Constants::PROJECT_APPLICATION_TOKEN_REGEXP
        . ')$'
      ),
    ],
    defaults: [
      'token' => Constants::NEW_APPLICATION_TOKEN,
    ],
  )]
  public function showShare():TemplateResponse
  {
    list($projectName, $token) = $this->parseToken();

    $nowDate = self::convertToTimezoneDate(new DateTimeImmutable, $this->dateTimeZone->getTimeZone());
    $currentYear = $nowDate->format('Y');

    $this->logInfo('YEAR ' . $currentYear);

    $projects = $this->entityManager->getRepository(Entities\Project::class)->findBy(
      criteria: [
        '>=year' => $currentYear,
      ],
      orderBy: [
        'year' => 'DESC',
        'name' => 'ASC',
      ],
    );

    $projectsList = [];
    $activeProject = -1;
    $timezone = $this->dateTimeZone->getTimeZone();

    /** @var Entities\Project $project */
    foreach ($projects as $project) {
      $this->logInfo('PROJECT ' . $project->getName());

      $startDate = $project->getRegistrationStartDate();
      if (empty($startDate)) {
        // there must be a registration start date, otherwise the registration
        // is considered not open.
        continue;
      }
      $startDate = self::convertToTimezoneDate($startDate, $timezone);
      if ($nowDate < $startDate) {
        continue;
      }

      $deadline = $this->registrationService->getProjectRegistrationDeadline($project);
      if (empty($deadline)) {
        // no events configured yet, no explicit deadline -> registration is
        // not yet open.
        continue;
      }
      $deadline = self::convertToTimezoneDate($deadline, $timezone);
      if ($nowDate > $deadline) {
        continue;
      }

      if (empty($projectName)) {
        $projectName = $project->getName();
      }

      if ($project->getName() == $projectName) {
        $activeProject = count($projectsList);
      }

      $instrumentationNumbers = $project->getInstrumentationNumbers();
      $flatInstrumentationNumbers = [];
      /** @var Entities\ProjectInstrumentationNumber $instrumentationNumber */
      foreach ($instrumentationNumbers as $instrumentationNumber) {
        $flatData = $instrumentationNumber->toArray();
        unset($flatData['instruments']);
        $flatData['project'] = $project->getId();
        $instrument = $instrumentationNumber->getInstrument();
        $flatInstrument = $instrument->toArray();
        unset($flatInstrument['musicianInstruments']);
        $flatInstrument['families'] = [];
        foreach ($instrument->getFamilies() as $family) {
          $flatFamily = $family->toArray();
          unset($flatFamily['instruments']);
          $flatInstrument['families'][] = $flatFamily;
        }
        usort($flatInstrument['families'], fn($a, $b) => strcmp($a['family'], $b['family']));
        $flatData['instrument'] = $flatInstrument;
        $flatInstrumentationNumbers[] = $flatData;
      }

      $participantFields = $project->getParticipantFields();
      $flatParticipantFields = [];
      /** @var Entities\ProjectParticipantField $participantField */
      foreach ($participantFields as $participantField) {
        switch ($participantField->getDataType()) {
        }
        $flatData = $participantField->toArray();
        $flatData['project'] = $project->getId();
        // needed:
        // - options
        // - default value
        // - absence field if there
        $absenceEvent = $participantField->getProjectEvent();
        $flatData['absenceEvent'] = $absenceEvent ? $absenceEvent->getId() : -1;
        $defaultValue = $participantField->getDefaultValue();
        if ($defaultValue) {
          $flatData['defaultValue'] = (string)$defaultValue->getKey();
        }
        $flatData['dataOptions'] = [];
        /** @var Entities\ProjectParticipantFieldDataOption $option */
        foreach ($participantField->getDataOptions() as $option) {
          $flatOption = $option->toArray();
          $flatOption['field'] = $participantField->getId();
          $flatOption['fieldData'] = [];
          /** @var Entities\ProjectParticipantFieldDatum $fieldDatum */
          foreach ($option->getFieldData() as $fieldDatum) {
            $flatDatum = $fieldDatum->toArray();
            $flatDatum['field'] = $participantField->getId();
            $flatDatum['project'] = $project->getId();
            $flatDatum['musician'] = $datum->getMusician()->getId();
            $flatOption['fieldData'][] = $flatDatum;
          }
          $flatData['dataOptions'][(string)$option->getKey()] = $flatOption;
        }
        $flatData['fieldData'] = [];
        /** @var Entities\ProjectParticipantFieldDatum $datum */
        foreach ($participantField->getFieldData() as $datum) {
          $flatDatum = $datum->toArray();
          $flatDatum['field'] = $participantField->getId();
          $flatDatum['project'] = $project->getId();
          $flatDatum['musician'] = $datum->getMusician()->getId();
          $flatData['fieldData'][] = $flatDatum;
        }
        unset($flatData['payments']);
        $flatParticipantFields[$participantField->getId()] = $flatData;
      }

      $calendarEvents = $project->getCalendarEvents();
      $flatCalendarEvents = [];
      /** @var Entities\ProjectEvent $projectEvent */
      foreach ($calendarEvents as $projectEvent) {
        $flatData = $projectEvent->toArray();
        unset($flatData['project']);
        $flatData['project'] = $project->getId();
        $absenceField = $projectEvent->getAbsenceField();
        $flatData['absenceField'] = $absenceField ? $absenceField->getId() : -1;
        $eventData = $this->eventsService->getEventData($projectEvent);
        if ($eventData['allday']) {
          $eventData['start'] = $eventData['start']->format('Y-m-d');
          $eventData['end'] = $eventData['end']->format('Y-m-d');
        } else {
          $eventData['start'] = $eventData['start']->format(DateTime::W3C);
          $eventData['end'] = $eventData['end']->format(DateTime::W3C);
        }
        unset($eventData['sibling']);
        unset($eventData['calendardata']);
        $flatData['calendarObject'] = $eventData;
        $flatCalendarEvents[] = $flatData;
      }

      $projectsList[] = [
        'id' => $project->getId(),
        'name' => $project->getName(),
        'year' => $project->getYear(),
        'startDate' => $startDate->format('Y-m-d'),
        'deadline' => $deadline->format('Y-m-d'),
        'instrumentation' => $flatInstrumentationNumbers,
        'participantFields' => $flatParticipantFields,
        'projectEvents' => $flatCalendarEvents,
      ];
    }

    if (!empty($this->userSession->getUser()) && $this->userSession->isLoggedIn()) {
      if ($activeProject >= 0) {
        $this->share = $this->registrationService->getApplicationShare($projectName, cloudUserId: $this->userSession->getUser()->getUID());
      }
      $response = new TemplateResponse($this->appName, 'project-registration', [
        'appName' => $this->appName,
        'public' => false,
      ]);
    } else {
      $response = new PublicTemplateResponse($this->appName, 'project-registration', [
        'appName' => $this->appName,
        'public' => true,
      ]);
      $response->setHeaderTitle($this->l->t('Project Application for %s', $projectName));
      $response->setFooterVisible(false);
    }

    $this->initialState->provideInitialState('projects', $projectsList);
    $this->initialState->provideInitialState('activeProject', $activeProject);
    $this->initialState->provideInitialState('token', $token);
    if ($this->share ?? null) {
      $this->initialState->provideInitialState(
        'applicationData',
        array_filter($this->share->getData(), fn($value, $key) => $key !== 'passwordHash', ARRAY_FILTER_USE_BOTH),
      );
    }

    // provide the available instruments
    $instruments = $this->entityManager->getRepository(Entities\Instrument::class)->findBy(
      [],
      [
        'sortOrder' => 'ASC',
      ],
    );
    $flatInstruments = [];
    foreach ($instruments as $instrument) {
      $flatInstrument = $instrument->toArray();
      unset($flatInstrument['musicianInstruments']);
      $flatInstrument['families'] = [];
      foreach ($instrument->getFamilies() as $family) {
        $flatFamily = $family->toArray();
        unset($flatFamily['instruments']);
        $flatInstrument['families'][] = $flatFamily;
      }
      usort($flatInstrument['families'], fn($a, $b) => strcmp($a['family'], $b['family']));
      $flatInstruments[] = $flatInstrument;
    }

    $this->initialState->provideInitialState('instruments', $flatInstruments);

    // provide the project instruments
    // @todo

    // provide country names
    $displayLocale = $this->l->getLocaleCode();
    $displayRegion = locale_get_region($displayLocale);
    if (empty($displayRegion)) {
      $displayRegion = strtoupper($displayLocale);
      $displayLocale = $displayLocale . '_' . $displayRegion;
    }

    $locales = resourcebundle_locales('');
    $countryCodes = [];
    foreach ($locales as $locale) {
      $country = locale_get_region($locale);
      if ($country) {
        $countryCodes[$country] = [
          'code' => $country,
          'name' => locale_get_display_region($locale, $displayLocale),
        ];
      }
    }
    usort($countryCodes, fn($a, $b) => strcmp($a['name'], $b['name']));
    $this->initialState->provideInitialState('countries', array_values($countryCodes));
    $this->initialState->provideInitialState('displayLocale', [
      'code' => $displayLocale,
      'region' => $displayRegion,
      'language' => locale_get_primary_language($displayLocale),
    ]);

    Util::addScript($this->appName, $this->assetService->getJSAsset('project-registration'));
    Util::addStyle($this->appName, $this->assetService->getCSSAsset('project-registration'));

    return $response;
  }

  /**
   * Receive the submit request, generate an email share and send all
   * neccessary data back to the frontend.
   *
   * @param string $token Project name and share token. For technical reasons
   * of route-matching and because of what is expected by the share controller
   * middleware the two parameters must come together.
   *
   * @param array $data User input, registration data.
   *
   * @return DataResponse
   *
   * @todo Mayhaps use a public template response. This causes a page reload
   * but this might even be desirable for security considerations.
   *
   * #[CoreAttributes\NoCSRFRequired]
   */
  #[CoreAttributes\NoAdminRequired]
  #[CoreAttributes\PublicPage]
  #[CoreAttributes\FrontpageRoute(
    url: '/registration/submit/{token}',
    verb: 'POST',
    requirements: [
      'token' => (
        '^('
        // plain project name, needs to be remapped from token to project name
        . Constants::TEMPORARY_PROJECT_NAME_REGEXP
        . '|'
        // composite project name and token, this is required as the
        // URLGenerator call in the public-share framework just allows one
        // parameter for the token and we decided to use the project-name +
        // some fancy hash
        . Constants::TEMPORARY_PROJECT_NAME_REGEXP . '/' . Constants::PROJECT_APPLICATION_TOKEN_REGEXP
        . ')$'
      ),
    ],
    defaults: [
      'token' => Constants::NEW_APPLICATION_TOKEN,
    ],
  )]
  public function submit(string $token, array $data): DataResponse
  {
    list($projectName, $token) = $this->parseToken();

    $this->registrationService->handleSubmission($projectName, $data, $token);
    return new DataResponse($data, Http::STATUS_OK);
  }

  /** {@inheritdoc} */
  public function isValidToken(): bool
  {
    list($projectName, $token) = $this->parseToken();
    if ($token === Constants::NEW_APPLICATION_TOKEN) {
      $this->logInfo('NEW REGISTRATION');
      return true;
    }
    if ($projectName === null) {
      $this->logInfo('NO PROJECT NAME');
      return false;
    }

    // Store project name and token in the PHP session in order to get access
    // to the view providing the application data.
    $this->registrationService->updateDatabaseRowAccessTokens([
      Constants::SQL_PROJECT_APPLICATION_PROJECT_NAME => $projectName,
      Constants::SQL_PROJECT_APPLICATION_SHARE_TOKENS => $token,
    ]);

    $this->share = $this->registrationService->getApplicationShare($projectName, applicationHash: $token);

    if ($this->share === null) {
      $this->logInfo('NO SHARE; REMOVING TOKENS');
      $this->registrationService->updateDatabaseRowAccessTokens(null);
    }

    return $this->share !== null;
  }

  /**
   * Install the DB row-access token into the session.
   *
   * @param bool $remove If \true remove the access token.
   *
   * @return void
   */
  private function setRowAccessToken(bool $remove = false): void
  {
    if (empty($this->share)) {
      $this->logInfo('NO SHARE; REMOVING TOKENS', [ 'exception' => new  \Exception('balh') ]);
      $this->registrationService->updateDatabaseRowAccessTokens(null);
      return;
    }
    $token = $remove ? null : $this->getPasswordHash();
    $this->registrationService->updateDatabaseRowAccessTokens([
      Constants::SQL_PROJECT_APPLICATION_ROW_ACCESS_TOKEN => $token,
    ]);
  }

  /** {@inheritdoc} */
  protected function verifyPassword(string $password): bool
  {
    if (!$this->registrationService->checkPassword($this->share, $password)) {
      $this->setRowAccessToken(remove: true);
      return false;
    }
    $this->setRowAccessToken();
    return true;
  }

  /** {@inheritdoc} */
  public function isAuthenticated(): bool
  {
    if (!parent::isAuthenticated()) {
      // $this->setRowAccessToken(remove: true);
      $this->logError('NOT AUTHENTICATED');
      return false;
    }
    $this->setRowAccessToken();
    return true;
  }

  /** {@inheritdoc} */
  protected function getPasswordHash(): ?string
  {
    return $this->share?->getPassword();
  }

  /** {@inheritdoc} */
  protected function isPasswordProtected(): bool
  {
    list(,$token) = $this->parseToken();
    return $token !== Constants::NEW_APPLICATION_TOKEN;
  }

  /** {@inheritdoc} */
  #[CoreAttributes\NoCSRFRequired]
  #[CoreAttributes\PublicPage]
  #[CoreAttributes\FrontpageRoute(
    url: '/registration/{token}/authenticate/{redirect}',
    verb: 'GET',
    requirements: [
      'token' => (
        '^'
        . Constants::TEMPORARY_PROJECT_NAME_REGEXP
        . '/'
        . Constants::PROJECT_APPLICATION_TOKEN_REGEXP
        . '$'
      ),
    ],
  )]
  public function showAuthenticate(): PublicTemplateResponse
  {
    $templateParameters = ['share' => $this->share];

    $this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($this->share, BeforeTemplateRenderedEvent::SCOPE_PUBLIC_SHARE_AUTH));

    $response = new PublicTemplateResponse('core', 'publicshareauth', $templateParameters);
    if ($this->share->getSendPasswordByTalk()) {
      $csp = new ContentSecurityPolicy();
      $csp->addAllowedConnectDomain('*');
      $csp->addAllowedMediaDomain('blob:');
      $response->setContentSecurityPolicy($csp);
    }

    return $response;
  }

  /** {@inheritdoc} */
  protected function showAuthFailed(): PublicTemplateResponse {
    $templateParameters = ['share' => $this->share, 'wrongpw' => true];

    $this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($this->share, BeforeTemplateRenderedEvent::SCOPE_PUBLIC_SHARE_AUTH));

    $response = new PublicTemplateResponse('core', 'publicshareauth', $templateParameters);
    if ($this->share->getSendPasswordByTalk()) {
      $csp = new ContentSecurityPolicy();
      $csp->addAllowedConnectDomain('*');
      $csp->addAllowedMediaDomain('blob:');
      $response->setContentSecurityPolicy($csp);
    }

    return $response;
  }

  /** {@inheritdoc} */
  protected function showIdentificationResult(bool $success = false): PublicTemplateResponse {
    $templateParameters = ['share' => $this->share, 'identityOk' => $success];

    $this->eventDispatcher->dispatchTyped(new BeforeTemplateRenderedEvent($this->share, BeforeTemplateRenderedEvent::SCOPE_PUBLIC_SHARE_AUTH));

    $response = new PublicTemplateResponse('core', 'publicshareauth', $templateParameters);
    if ($this->share->getSendPasswordByTalk()) {
      $csp = new ContentSecurityPolicy();
      $csp->addAllowedConnectDomain('*');
      $csp->addAllowedMediaDomain('blob:');
      $response->setContentSecurityPolicy($csp);
    }

    return $response;
  }

  /** {@inheritdoc} */
  protected function validateIdentity(?string $identityToken = null): bool {
    if ($this->share->getShareType() !== IShare::TYPE_EMAIL) {
      return false;
    }

    if ($identityToken === null || $this->share->getSharedWith() === null) {
      return false;
    }

    return $identityToken === $this->share->getSharedWith();
  }

  /** {@inheritdoc} */
  protected function generatePassword(): void {
    $event = new GenerateSecurePasswordEvent(PasswordContext::SHARING);
    $this->eventDispatcher->dispatchTyped($event);
    $password = $event->getPassword() ?? $this->secureRandom->generate(20);

    $this->registrationService->updateApplicationData($this->share, $password);
  }

  /**
   * The token may contain the project tag. If so, strip it off. Return an
   * array composed of the project-name and the token.
   *
   * @return array
   */
  private function parseToken(): array
  {
    $projectName = null;
    $token = $this->getToken();
    if (empty($token)) {
      $this->logInfo('EMPTY TOKEN -> NEW');
      $token = Constants::NEW_APPLICATION_TOKEN;
    } elseif (str_contains($token, '/')) {
      $this->logInfo('COMPOUND TOKEN -> SPLIT');
      list($projectName, $token) = explode('/', $token);
    } elseif (preg_match('/^[A-Z]\w+\d{4}$/', $token)) {
      $this->logInfo('TOKEN IS PROJECT -> NEW ' . $token);
      $projectName = $token;
      $token = Constants::NEW_APPLICATION_TOKEN;
    }
    $projectName = $this->request->getParam('projectName', $projectName);
    return [$projectName, $token];
  }
}

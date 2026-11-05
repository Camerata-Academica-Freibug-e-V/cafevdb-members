<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2025, 2026 Claus-Justus Heine>
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

use DateInterval;
use DateTimeInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use UnexpectedValueException;
use Throwable;

use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Exception\DriverException;
use OCA\CAFeVDBMembers\Wrapped\Doctrine\DBAL\Query;

use OCP\Calendar\ICalendar;
use OCP\Calendar\ICalendarQuery;
use OCP\Calendar\IManager as ICalendarMananger;
use OCP\Defaults;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Mail\IMailer;
use OCP\Security\IHasher;
use OCP\Util;
use Psr\Log\LoggerInterface;

use OCA\CAFeVDBMembers\Constants;
use OCA\CAFeVDBMembers\Controller\SettingsController;
use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;
use OCA\CAFeVDBMembers\Database\ORM\Repositories\EntityRepository;
use OCA\CAFeVDBMembers\Exceptions;
use OCA\CAFeVDBMembers\Model\ApplicationShare;
use OCA\CAFeVDBMembers\Settings\ConfigConstants;
use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits as ToolkitTraits;

/**
 * Service class for managing project registrations, generating shares,
 * notifications and so on.
 */
class ProjectRegistrationService
{
  use ToolkitTraits\LoggerTrait;

  public const PERSONAL_PROFILE_KEY = 'personalProfile';
  public const EMAIL_KEY = 'email';
  public const USER_ID_KEY = 'uid';
  public const PROJECT_KEY = 'project';

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    protected Defaults $defaults,
    protected EntityManager $entityManager,
    protected IAppConfig $appConfig,
    protected ICalendarMananger $calendarManager,
    protected IConfig $config,
    protected IHasher $hasher,
    protected IL10N $l,
    protected IMailer $mailer,
    protected ISession $session,
    protected IURLGenerator $urlGenerator,
    protected IUserSession $userSession,
    protected LoggerInterface $logger,
    protected string $appName,
    protected string $orchestraAppName,
  ) {
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * Wrap the given database entity into an ApplicationShare envelope.
   *
   * @param Entities\ProjectApplication $projectApplication
   *
   * @return ApplicationShare
   */
  private function shareFromApplicationEntity(Entities\ProjectApplication $projectApplication):ApplicationShare
  {
    $registrationReplyTo = $this->appConfig->getValueString($this->appName, ConfigConstants::REGISTRATION_REPLY_TO_KEY);

    $share = new ApplicationShare($projectApplication, $registrationReplyTo);

    $share->setExpirationDate(Carbon::createFromImmutable($this->getProjectRegistrationDeadline($projectApplication->getProject())));

    $share->setNote($this->l->t('Your application has been submitted successfully and will be reviewed by the executive board.
We will contact you again with further information latest after the end of the registration deadline. In the unfortunate case
that we have to decline your application we will inform you ASAP.'));

    return $share;
  }

  /**
   * Data submission, this is more-or-less the main entry point.
   *
   * @param string $projectName
   *
   * @param array $data The user submitted registration data.
   *
   * @param null|string $oldApplicationHash Hash of the primary email address.
   *
   * @return void
   *
   * @throws Exceptions\RegistrationDataMissingException
   * @throws UnexpectedValueException
   */
  public function handleSubmission(string $projectName, array $data, ?string $oldApplicationHash = null): void
  {
    // $this->logInfo('Submission Data ' . print_r($data, true));
    $primaryEmail = $data[self::PERSONAL_PROFILE_KEY][self::EMAIL_KEY] ?? null;
    if ($primaryEmail === null) {
      throw new Exceptions\RegistrationDataMissingException(
        message: (
          $this->l->t('The field "%1$s" in the submitted registration data is missing.', 'email')
          . ' '
          . $this->l->t('Unfortunately, we cannot do without a valid email address as we need some means to communication with the persions applying of participation.')
        ),
      );
    }

    if (!$this->mailer->validateMailAddress($primaryEmail)) {
      throw new Exceptions\RegistrationDataMissingException(
        message: (
          $this->l->t('The data "%1$s" does not seem to be a valid email address.', $primaryEmail)
          . ' '
          . $this->l->t('Unfortunately, we cannot do without a valid email address as we need some means to communication with the persions applying of participation.')
        ),
      );
    }

    // The email is the token that we use for identification
    $applicationHash = hash(Constants::EMAIL_HASH_ALGORITHM, $primaryEmail);
    if ($oldApplicationHash === null) {
      $oldApplicationHash = $applicationHash;
    }

    // install old and new application hash for db access
    $this->updateDatabaseRowAccessTokens([
      Constants::SQL_PROJECT_APPLICATION_PROJECT_NAME => $projectName,
      Constants::SQL_PROJECT_APPLICATION_SHARE_TOKENS => implode(',', array_unique([$applicationHash, $oldApplicationHash])),
    ]);

    /** @var Entities\Project */
    $project = $this->entityManager->getRepository(Entities\Project::class)->findOneBy([
      'name' => $projectName,
    ]);

    /** @var EntityRepository $repository */
    $repository = $this->entityManager->getRepository(Entities\ProjectApplication::class);

    /** @var Entities\ProjectApplication $oldProjectApplication */
    $oldProjectApplication = $repository->findOneBy([
      'project.name' => $projectName,
      'email#SHA2(%s, 256)' => $oldApplicationHash,
    ]);
    $oldUid = $oldProjectApplication?->getMusician()?->getUserIdSlug();

    $this->entityManager->beginTransaction();
    try {

      if ($oldApplicationHash != $applicationHash || $oldProjectApplication === null) {
        /** @var Entities\ProjectApplication $projectApplication */
        $projectApplication = new Entities\ProjectApplication(
          $project,
          $primaryEmail,
          musician: $oldProjectApplication?->getMusician(),
          data: $data,
        );
      } else {
        $projectApplication = $oldProjectApplication;
      }

      // Remember the UID and also keep any previously submitted UID. We treat
      // the email address as unique identifier here.
      if ($this->userSession->isLoggedIn()) {
        $uid = $this->userSession->getUser()->getUID();
        if ($oldUid !== null && $oldUid !== $uid) {
          throw new UnexpectedValueException(
            $this->l->t(
              'The UID "%1$s" stored in the previously submitted application differs from the uid "%2$s" of the current user.',
              [$oldUid, $uid],
            ),
          );
        }
        $musician = $this->entityManager->getRepository(Entities\Musician::class)->findOneBy([
          'userIdSlug' => $uid,
        ]);
        $projectApplication->setMusician($musician);
      }
      $projectApplication->setProject($project);
      $oldCreated = $oldProjectApplication?->getCreated();
      $projectApplication->setPasswordHash($oldProjectApplication?->getPasswordHash());
      $projectApplication->setData($data);

      $this->entityManager->persist($projectApplication);
      $this->entityManager->flush();

      $projectApplication->setCreated($oldCreated ?? new CarbonImmutable());

      if ($oldProjectApplication && $oldProjectApplication !== $projectApplication) {
        $this->entityManager->remove($oldProjectApplication);
      }

      $this->entityManager->flush();

      $this->entityManager->commit();
    } catch (Throwable $t) {
      $this->entityManager->rollback();

      $sql = null;
      $params = null;

      /** @var DriverException $t */
      if (($t instanceof DriverException) && $t->getQuery() !== null) {
        // try to get hold of the SQL statement
        /** @var Query $query */
        $query = $t->getQuery();
        $sql = $query->getSQL();
        $params = $query->getParams();
      }

      throw new Exceptions\DatabaseException(
        $sql !== null
        ? $this->l->t('Unable to store the application data in the database ("%1$s", %2$s).', [
          $sql,
          print_r($params, true),
        ])
        : $this->l->t('Unable to store the application data in the database'),
        previous: $t,
        sql: $sql,
        params: $params,
      );
    }
    $this->sendEmail($this->shareFromApplicationEntity($projectApplication), [$primaryEmail]);
  }

  /**
   * Find possibly existing application data for the given email-hash and
   * project-name and wrap it into the IShare interface.
   *
   * @param string $projectName
   *
   * @param null|string $applicationHash
   *
   * @param null|string $cloudUserId
   *
   * @return null|ApplicationShare
   */
  public function getApplicationShare(
    string $projectName,
    ?string $applicationHash = null,
    ?string $cloudUserId = null,
  ): ?ApplicationShare {

    /** @var EntityRepository $repository */
    $repository = $this->entityManager->getRepository(Entities\ProjectApplication::class);

    $criteria = [ [ 'project.name' => $projectName ] ];
    if ($applicationHash !== null) {
      $criteria[] = [ 'email#SHA2(%s, 256)' => $applicationHash ];
    }
    if ($cloudUserId !== null) {
       $criteria[] = [ 'musician.userIdSlug' => $cloudUserId ];
    }

    /** @var Entities\ProjectApplication $projectApplication */
    $projectApplication = $repository->findOneBy($criteria);

    if ($projectApplication === null) {
      $this->logError('Unable to find old data given criteria ' . print_r($criteria, true));
      return null;
    }

    return $this->shareFromApplicationEntity($projectApplication);
  }

  /**
   * @param ApplicationShare $share
   *
   * @param null|string $password
   *
   * @return bool
   */
  public function checkPassword(ApplicationShare $share, ?string $password): bool
  {

    // if there is no password on the share object / passsword is null, there is nothing to check
    if ($password === null || $share->getPassword() === null) {
      return false;
    }

    // Makes sure password hasn't expired
    $expirationTime = $share->getPasswordExpirationTime();
    if ($expirationTime !== null && $expirationTime < new CarbonImmutable()) {
      return false;
    }

    $newHash = '';
    if (!$this->hasher->verify($password, $share->getPassword(), $newHash)) {
      return false;
    }

    if (!empty($newHash)) {
      $share->setPassword($newHash);
      $this->updateApplicationData($share);
    }

    return true;
  }

  /**
   * Possibly update and emit the row access tokens.
   *
   * @var array $tokens New tokens which override existing tokens. The new
   * tokens are merged into the existing set of access tokens. Pass \null in
   * order to remove all tokens.
   *
   * @return void
   */
  public function updateDatabaseRowAccessTokens(?array $tokens = []):void
  {
    $applicationTokens = $this->session->get(Constants::APPLICATION_SESSION_KEY) ?? [];
    if ($tokens === null) {
      $applicationTokens = array_map(fn(?string $value) => null, $applicationTokens);
    } else {
      $applicationTokens = array_merge($applicationTokens, $tokens);
    }
    $this->session->set(Constants::APPLICATION_SESSION_KEY, $applicationTokens);
    $this->entityManager->emitRowAccessTokens(); // install into the active DB session.
  }

  /**
   * Sync the provided application data to disk and possibly send out a password notification.
   *
   * @param ApplicationShare $share Dummy share wrapping the application data.
   *
   * @param string $plainTextPassword
   *
   * @return ApplicationShare
   *
   * @throws Exceptions\DatabaseException
   */
  public function updateApplicationData(ApplicationShare $share, ?string $plainTextPassword = null): ApplicationShare
  {
    $passwordChanged = !empty($plainTextPassword)
      && (empty($share->getPassword())
          || !$this->hasher->verify($plainTextPassword, $share->getPassword()));

    if ($passwordChanged) {
      $share->setPassword($this->hasher->hash($plainTextPassword));
    }

    $this->entityManager->beginTransaction();
    try {
      $this->entityManager->flush();
      $this->entityManager->commit();
    } catch (Throwable $t) {
      $this->entityManager->rollback();

      throw new Exceptions\DatabaseException(
        $this->l->t('Unable to store the application data in the database.'),
        previous: $t,
      );
    }

    if ($passwordChanged) {
      $this->sendPassword($share, $plainTextPassword, [ $share->getSharedWith() ]);
    }

    $this->updateDatabaseRowAccessTokens([
      Constants::SQL_PROJECT_APPLICATION_ROW_ACCESS_TOKEN => $share->getPassword(),
    ]);

    return $share;
  }

  /**
   * Compute the effective project registration deadline.
   *
   * @param Entities\Project $project
   *
   * @return null|DateTimeInterface
   */
  public function getProjectRegistrationDeadline(Entities\Project $project):?DateTimeInterface
  {
    $deadline = $project->getRegistrationDeadline();
    if (!empty($deadline)) {
      return $deadline;
    }

    $shareOwner = $this->appConfig->getValueString($this->orchestraAppName, ConfigConstants::SHARE_OWNER_KEY);
    if (empty($shareOwner)) {
      return null;
    }

    $principalUri = 'principals/users/' . $shareOwner;
    $projectCategory = $project->getName();
    $query = $this->calendarManager->newQuery($principalUri);
    $query->addSearchProperty(ICalendarQuery::SEARCH_PROPERTY_CATEGORIES);
    $query->setSearchPattern($projectCategory);
    $query->addSearchCalendar(ConfigConstants::REHEARSALS_CALENDAR_URI);
    $query->addSearchCalendar(ConfigConstants::CONCERTS_CALENDAR_URI);

    $calendarObjects = $this->calendarManager->searchForPrincipal($query);

    if (empty($calendarObjects)) {
      return null;
    }

    $startDates = [];

    foreach ($calendarObjects as $objectInfo) {
      foreach ($objectInfo['objects'] as $calendarObject) {
        $startDates[] = $calendarObject['DTSTART'][0];
        $this->logInfo('START ' . print_r($calendarObject['DTSTART'][0], true));
      }
    }

    $deadline = min($startDates)->modify('-1 day');

    return $deadline;
  }

  /**
   * Borrowed and adapted from apps/sharebymail.
   *
   * @param ApplicationShare $share The share to send the email for
   *
   * @param array $emails The email addresses to send the email to
   *
   * @return void
   *
   * @todo This should compose the entire notification email, including debit
   * mandate form, terms of services, a record of the submitted data. This is
   * very far from being finished.
   */
  protected function sendEmail(ApplicationShare $share, array $emails): void
  {
    $link = $this->urlGenerator->linkToRouteAbsolute($this->appName . '.ProjectRegistration.showShare', [
      'token' => $share->getToken()
    ]);

    /** @var Entities\ProjectApplication $projectApplication */
    $projectApplication = $share->getNode();

    $projectName = $projectApplication->getProject()->getName();

    $expiration = $share->getExpirationDate();
    $note = $share->getNote();
    $shareWith = $share->getSharedWith();

    $initiatorDisplayName = $this->appConfig->getValueString($this->orchestraAppName, ConfigConstants::ORCHESTRA_NAME_KEY);
    $initiatorEmailAddress = $share->getSharedBy();
    $message = $this->mailer->createMessage();

    $emailTemplate = $this->mailer->createEMailTemplate($this->appName . '.ApplicantNotification', [
      'projectName' => $projectName,
      'link' => $link,
      'initiator' => $initiatorDisplayName,
      'initiatorEmail' => $initiatorEmailAddress,
      'expiration' => $expiration,
      'shareWith' => $shareWith,
      'note' => $note,
    ]);

    $emailTemplate->setSubject($this->l->t(
      'Your application for the project "%1$s" of the orchestra "%2$s"', [
        $projectName,
        $initiatorDisplayName,
      ],
    ));
    $emailTemplate->addHeader();
    $emailTemplate->addHeading($this->l->t('Your application for "%1$s"', $projectName), false);

    $emailTemplate->addBodyListItem(
      htmlspecialchars($note),
      $this->l->t('Note:'),
      $this->getAbsoluteImagePath('caldav/description.png'),
      $note
    );

    if ($expiration !== null) {
      $dateString = (string)$this->l->l('date', $expiration, ['width' => 'medium']);
      $emailTemplate->addBodyListItem(
        $this->l->t('You can modify your application until the end of the application period, %s.', [$dateString]),
        $this->l->t('Expiration:'),
        $this->getAbsoluteImagePath('caldav/time.png'),
      );
    }

    $emailTemplate->addBodyButton(
      $this->l->t('Review your application for "%s"', [$projectName]),
      $link
    );

    // If multiple recipients are given, we send the mail to all of them
    if (count($emails) > 1) {
      // We do not want to expose the email addresses of the other recipients
      $message->setBcc($emails);
    } else {
      $message->setTo($emails);
    }

    // The "From" contains the sharers name
    $instanceName = $this->defaults->getName();
    $senderName = $instanceName;
    $senderName = $this->l->t(
      '%1$s via %2$s',
      [
        $initiatorDisplayName,
        $instanceName
      ]
    );
    $message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);

    $message->setReplyTo([$initiatorEmailAddress => $initiatorDisplayName]);
    $emailTemplate->addFooter($instanceName . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : ''));

    $message->useTemplate($emailTemplate);
    $failedRecipients = $this->mailer->send($message);
    if (!empty($failedRecipients)) {
      $this->logger->error('Share notification mail could not be sent to: ' . implode(', ', $failedRecipients));
      return;
    }
  }

  /**
   * Borrowed and adapted from apps/sharebymail.
   *
   * @param ApplicationShare $share Dummy share wrapping the application data.
   *
   * @param string $password The new password.
   *
   * @param array $emails Recipient emails. We keep this as array although in
   * our case we always only have one recipient.
   *
   * @return bool
   *
   * @todo Customize further.
   */
  private function sendPassword(ApplicationShare $share, string $password, array $emails): bool
  {
    if ($password === '' || $share->getSendPasswordByTalk()) {
      return false;
    }

    /** @var Entities\ProjectApplication $projectApplication */
    $projectApplication = $share->getNode();

    $projectName = $projectApplication->getProject()->getName();
    $shareWith = $share->getSharedWith();
    $initiatorDisplayName = $this->appConfig->getValueString($this->orchestraAppName, ConfigConstants::ORCHESTRA_NAME_KEY);
    $initiatorEmailAddress = $share->getSharedBy();

    $htmlBodyPart =
      $plainBodyPart = $this->l->t(
        'You have submitted an application to participate in the project "%1$s" of the orchestra "%2$s".'
        . ' You should have already received a separate mail with a link to access your application data.', [
          $projectName,
          $initiatorDisplayName,
        ],
      );

    $message = $this->mailer->createMessage();

    $emailTemplate = $this->mailer->createEMailTemplate($this->appName . '.RecipientPasswordNotification', [
      'projectName' => $projectName,
      'password' => $password,
      'initiator' => $initiatorDisplayName,
      'initiatorEmail' => $initiatorEmailAddress,
      'shareWith' => $shareWith,
    ]);

    $emailTemplate->setSubject($this->l->t(
      'Password to access your application for "%1$s" of the orchestra "%2$s"', [
        $projectName,
        $initiatorDisplayName,
      ],
    ));
    $emailTemplate->addHeader();
    $emailTemplate->addHeading($this->l->t('Password to access your application for "%s"', [$projectName]), false);
    $emailTemplate->addBodyText(htmlspecialchars($htmlBodyPart), $plainBodyPart);
    $emailTemplate->addBodyText($this->l->t('It is protected with the following password:'));
    $emailTemplate->addBodyText($password);

    if ($this->config->getSystemValue('sharing.enable_mail_link_password_expiration', false) === true) {
      $expirationTime = new CarbonImmutable();
      $expirationInterval = $this->config->getSystemValue('sharing.mail_link_password_expiration_interval', 3600);
      $expirationTime = $expirationTime->add(new DateInterval('PT' . $expirationInterval . 'S'));
      $emailTemplate->addBodyText($this->l->t('This password will expire at %s', [$expirationTime->format('r')]));
    }

    // If multiple recipients are given, we send the mail to all of them
    if (count($emails) > 1) {
      // We do not want to expose the email addresses of the other recipients
      $message->setBcc($emails);
    } else {
      $message->setTo($emails);
    }

    // The "From" contains the sharers name
    $instanceName = $this->defaults->getName();
    $senderName = $instanceName;
    $senderName = $this->l->t(
      '%1$s via %2$s',
      [
        $initiatorDisplayName,
        $instanceName
      ]
    );
    $message->setFrom([Util::getDefaultEmailAddress($instanceName) => $senderName]);

    $message->setReplyTo([$initiatorEmailAddress => $initiatorDisplayName]);
    $emailTemplate->addFooter($instanceName . ($this->defaults->getSlogan() !== '' ? ' - ' . $this->defaults->getSlogan() : ''));

    $message->useTemplate($emailTemplate);
    $failedRecipients = $this->mailer->send($message);
    if (!empty($failedRecipients)) {
      $this->logger->error('Share password mail could not be sent to: ' . implode(', ', $failedRecipients));
      return false;
    }

    // $this->createPasswordSendActivity($share, $shareWith, false);
    return true;
  }

  /**
   * @param string $path
   *
   * @return string
   */
  private function getAbsoluteImagePath(string $path):string
  {
    return $this->urlGenerator->getAbsoluteURL(
      $this->urlGenerator->imagePath('core', $path)
    );
  }
}

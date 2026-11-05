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

namespace OCA\CAFeVDBMembers\Model;

use DateTime;
use DateTimeInterface;
use UnexpectedValueException;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Node;
use OCP\Share\IAttributes;
use OCP\Share\IShare;
use OC\Share20\ShareAttributes;

use OCA\CAFeVDBMembers\Constants;
use OCA\CAFeVDBMembers\Database\ORM\Entities;

/**
 * This interface must not implemented in your application. Oops. Oh,
 * well, and now?
 */
class ApplicationShare implements IShare
{
  protected ?DateTimeInterface $expirationDate = null;

  protected ?string $note = null;

  /**
   * @phpcs:disable Squiz.Functions.MultiLineFunctionDeclaration.BraceOnSameLine
   * @phpcs:disable Squiz.Functions.MultiLineFunctionDeclaration.ContentAfterBrace
   * @phpcs:disable Squiz.WhiteSpace.ScopeClosingBrace.ContentBefore
   * @phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
   * @phpcs:disable Squiz.Commenting.FunctionComment.Missing
   */
  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    protected Entities\ProjectApplication $applicationData,
    protected string $replyTo,
  ) {}
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * {@inheritdoc}
   */
  public function isPasswordProtected(): bool
  {
    return true;
  }

  /**
   * @return The underlying data array.
   */
  public function getData():array
  {
    return $this->applicationData->getData();
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id): IShare
  {
    return $this;
  }

  /** {@inheritdoc} */
  public function getId(): string
  {
    return $this->getSharedWith() . '#' . $this->applicationData->getProject()->getName();
  }

  /** {@inheritdoc} */
  public function getFullId(): string
  {
    return $this->appName . ':' . $this->getId();
  }

  /** {@inheritdoc} */
  public function setProviderId($id): IShare
  {
    return $this;
  }

  /** {@inheritdoc} */
  public function setNode(Node $node): IShare
  {
    return $this;
  }

  /** {@inheritdoc} */
  public function getNode(): Node {
    return $this->applicationData;
  }

  /** {@inheritdoc} */
  public function setNodeId($id): IShare
  {
    return $this;
  }

  /** {@inheritdoc} */
  public function getNodeId(): int
  {
    return -1;
  }

  /** {@inheritdoc} */
  public function setNodeType($id): IShare
  {
    return $this;
  }

  /** {@inheritdoc} */
  public function getNodeType(): string
  {
    return 'custom';
  }

  /** {@inheritdoc} */
  public function setShareType($shareType): IShare
  {
    return $this;
  }

  /** {@inheritdoc} */
  public function getShareType()
  {
    return IShare::TYPE_EMAIL;
  }

  /** {@inheritdoc} */
  public function setSharedWith($sharedWith) {}

  /** {@inheritdoc} */
  public function getSharedWith() { return $this->getData()['personalProfile']['email']; }

  /** {@inheritdoc} */
  public function setSharedWithDisplayName($displayName) {}

  /** {@inheritdoc} */
  public function getSharedWithDisplayName() {
    $nickName = $this->getData()['personalProfile']['nickName'];
    $firstName = $this->getData()['personalProfile']['firstName'];
    $surName = $this->getData()['personalProfile']['surName'];

    return (empty($nickName) ? $firstName : $nickName) . ' ' . $surName;
  }

  /** {@inheritdoc} */
  public function setSharedWithAvatar($src) {}

  /** {@inheritdoc} */
  public function getSharedWithAvatar() { return ''; }

  /** {@inheritdoc} */
  public function setPermissions($permissions) {}

  /** {@inheritdoc} */
  public function getPermissions() { return CoreConstants::PERMISSION_READ; }

  /** {@inheritdoc} */
  public function newAttributes(): IAttributes { return new ShareAttributes ; }

  /** {@inheritdoc} */
  public function setAttributes(?IAttributes $attributes) {}

  /** {@inheritdoc} */
  public function getAttributes(): ?IAttributes { return null; }

  /** {@inheritdoc} */
  public function setStatus(int $status): IShare { return $this; }

  /** {@inheritdoc} */
  public function getStatus(): int { return IShare::STATUS_ACCEPTED; }

  /** {@inheritdoc} */
  public function setNote($note) {
    $this->note = $note;
    return $this;
  }

  /** {@inheritdoc} */
  public function getNote() { return $this->note; }

  /** {@inheritdoc} */
  public function setExpirationDate(?DateTime $expireDate) {
    $this->expirationDate = $expireDate;
    return $this;
  }

  /** {@inheritdoc} */
  public function getExpirationDate() { return $this->expirationDate; }

  /** {@inheritdoc} */
  public function setNoExpirationDate(bool $noExpirationDate) {}

  /** {@inheritdoc} */
  public function getNoExpirationDate() { return true; }

  /** {@inheritdoc} */
  public function isExpired() { return false; }

  /** {@inheritdoc} */
  public function setLabel($label) {}

  /** {@inheritdoc} */
  public function getLabel() { return ''; }

  /** {@inheritdoc} */
  public function setSharedBy($sharedBy) { $this->replyTo = $sharedBy; }

  /** {@inheritdoc} */
  public function getSharedBy() { return $this->replyTo; }

  /** {@inheritdoc} */
  public function setShareOwner($shareOwner) { $this->replyTo = $shareOwner; }

  /** {@inheritdoc} */
  public function getShareOwner() { return $this->replyTo; }

  /** {@inheritdoc} */
  public function setPassword($password) {
    $this->applicationData->setPasswordHash($password);
  }

  /** {@inheritdoc} */
  public function isPasswordHashed(): bool
  {
    return true;
  }

  /** {@inheritdoc} */
  public function setPasswordHash(string $passwordHash): IShare
  {
    throw new UnexpectedValueException('I DO NOT EXPECT TO RECEIVE AN ALREADY HASHED PASSWORD');
  }

  /** {@inheritdoc} */
  public function getPassword() { return $this->applicationData->getPasswordHash(); }

  /** {@inheritdoc} */
  public function setPasswordExpirationTime(?DateTimeInterface $passwordExpirationTime = null): IShare {}

  /** {@inheritdoc} */
  public function getPasswordExpirationTime(): ?DateTimeInterface { return null; }

  /** {@inheritdoc} */
  public function setSendPasswordByTalk(bool $sendPasswordByTalk) {}

  /** {@inheritdoc} */
  public function getSendPasswordByTalk(): bool { return false; }

  /** {@inheritdoc} */
  public function setToken($token) {}

  /**
   * Return the full composite token, including the project name.
   *
   * {@inheritdoc}
   *
   * @return string
   */
  public function getToken()
  {
    $projectName = $this->applicationData->getProject()->getName();
    $emailHash = hash(Constants::EMAIL_HASH_ALGORITHM, $this->getSharedWith());

    return $projectName . '/' . $emailHash;
  }

  /** {@inheritdoc} */
  public function setParent(int $parent): IShare { return $this; }

  /** {@inheritdoc} */
  public function getParent(): ?int { return null; }

  /** {@inheritdoc} */
  public function setTarget($target) { return $this; }

  /** {@inheritdoc} */
  public function getTarget() { return ''; }

  /** {@inheritdoc} */
  public function getOriginalTarget(): ?string { return null; }

  /** {@inheritdoc} */
  public function setShareTime(DateTime $shareTime) {}

  /** {@inheritdoc} */
  public function getShareTime() { return new DateTime; }

  /** {@inheritdoc} */
  public function setMailSend($mailSend) {}

  /** {@inheritdoc} */
  public function getMailSend() { return true; }

  /** {@inheritdoc} */
  public function setNodeCacheEntry(ICacheEntry $entry) {}

  /** {@inheritdoc} */
  public function getNodeCacheEntry() { return null; }

  /** {@inheritdoc} */
  public function setHideDownload(bool $hide): IShare {}

  /** {@inheritdoc} */
  public function getHideDownload(): bool { return false; }

  /** {@inheritdoc} */
  public function setReminderSent(bool $reminderSent): IShare { return $this; }

  /** {@inheritdoc} */
  public function getReminderSent(): bool { return false; }

  /** {@inheritdoc} */
  public function canSeeContent(): bool { return true; }

  /** {@inheritdoc} */
  public function canDownload(): bool { return true; }
}

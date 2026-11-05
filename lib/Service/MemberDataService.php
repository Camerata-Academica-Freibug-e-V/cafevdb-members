<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2024, 2026 Claus-Justus Heine
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

use Psr\Log\LoggerInterface;
use OCP\IL10N;

use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumParticipantFieldDataType as FieldDataType;
use OCA\CAFeVDBMembers\Database\DBAL\Types\EnumParticipantFieldMultiplicity as FieldMultiplicity;
use OCA\CAFeVDBMembers\Database\ORM\Entities;
use OCA\CAFeVDBMembers\Database\ORM\EntityManager;

/**
 * Some support functions for giving access to the private data of a member.
 */
class MemberDataService
{
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\LoggerTrait;
  use \OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Traits\UtilTrait;

  const PATH_SEP = '/';

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private EntityManager $entityManager,
    protected LoggerInterface $logger,
    protected IL10N $l,
  ) {
  }
  // phpcs:enable

  /**
   * Generates e.g. passport-clausJustusHeine.pdf.
   *
   * @param string $base
   *
   * @param Entities\Musician $musician
   *
   * @return string
   */
  public function participantFilename(string $base, Entities\Musician $musician):string
  {
    $userIdSlug = $musician->getUserIdSlug();
    return $base . '-' . self::dashesToCamelCase($userIdSlug, true, '_-.');
  }

  /**
   * Fetch the (encrypted) file for a field-datum and return path-info like
   * information for the file.
   *
   * @param Entities\ProjectParticipantFieldDatum $fieldDatum
   *
   * @param bool $includeDeleted
   *
   * @return null|array
   * ```
   * [
   *   'file' => ENTITY,
   *   'baseName' => BASENAME, // generated, l10n
   '   'dirName' => DIRENAME, // generated, l10n
   *   'extension' => FILE EXTENSION, // from db-file
   *   'fileName' => FILENAME, // basename without extension
   *   'pathName' => DIRNAME/BASENAME,
   *   'dbFileName' => FILENAME_AS_STORED_IN_DB_TABLE,
   * ]
   * ```
   */
  public function participantFileInfo(Entities\ProjectParticipantFieldDatum $fieldDatum, bool $includeDeleted = false):?array
  {
    if (!$includeDeleted && $fieldDatum->isDeleted()) {
      return null;
    }
    /** @var Entities\ProjectParticipantField $field */
    $field = $fieldDatum->getField();
    if (!$includeDeleted && $field->isDeleted()) {
      return null;
    }
    /** @var Entities\ProjectParticipantFieldDataOption $fieldOption */
    $fieldOption = $fieldDatum->getDataOption();
    if (!$includeDeleted && $fieldOption->isDeleted()) {
      return null;
    }
    $dataType = $field->getDataType();
    switch ($dataType) {
      case FieldDataType::DB_FILE:
        $fileId = (int)$fieldDatum->getOptionValue();
        $file = $this->entityManager->find(Entities\DatabaseStorageFile::class, $fileId);
        break;
      case FieldDataType::LIABILITIES:
      case FieldDataType::RECEIVABLES:
        $file = $fieldDatum->getSupportingDocument();
        break;
      default:
        return null;
    }
    if (empty($file)) {
      return null;
    }
    /** @var Entities\DatabaseStorageFile $file */
    $dbFileName = $file->getFileName();
    $extension = pathinfo($dbFileName, PATHINFO_EXTENSION);
    $fieldName = $field->getName();

    if ($field->getMultiplicity() == FieldMultiplicity::SIMPLE) {
      // construct the file-name from the field-name
      $fileName = $this->participantFilename($fieldName, $fieldDatum->getMusician());
      $dirName = null;
    } else {
      // construct the file-name from the option label if non-empty or the file-name of the DB-file
      $optionLabel = $fieldOption->getLabel();
      if (!empty($optionLabel)) {
        $fileName = $this->participantFilename($fieldOption->getLabel(), $fieldDatum->getMusician());
      } else {
        $fileName = basename($dbFileName, '.' . $extension);
      }
      $dirName = $fieldName;
    }
    $baseName = $fileName . '.' . $extension;
    $pathName = empty($dirName) ? $baseName : $dirName . self::PATH_SEP . $baseName;
    return [
      'file' => $file,
      'baseName' => $baseName,
      'dirName' => $dirName,
      'extension' => $extension,
      'fileName' => $fileName,
      'pathName' => $pathName,
      'dbFileName' => $dbFileName,
    ];
  }
}

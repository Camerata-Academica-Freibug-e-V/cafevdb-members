<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers;

use Spatie\TypeScriptTransformer\Attributes as TSAttributes;

use OCA\CAFeVDBMembers\Wrapped\OCA\RotDrop\Toolkit\Constants as ToolkitConstants;

/** A couple of constants in order to avoid string literals. */
#[TSAttributes\TypeScript]
class Constants extends ToolkitConstants
{
  public const APPLICATION_SESSION_KEY = 'projectApplication';
  public const NEW_APPLICATION_TOKEN = 'new';
  public const EMAIL_HASH_ALGORITHM = 'sha256';

  /** @var string Name of the orchestra app (cafevdb) which provides the database. */
  public const ORCHESTRA_APP_NAME = 'cafevdb';

  // Names of the SQL session variables of the cloud-connector database.
  public const SQL_PROJECT_APPLICATION_ROW_ACCESS_TOKEN = 'PROJECT_APPLICATION_ROW_ACCESS_TOKEN';
  public const SQL_PROJECT_APPLICATION_SHARE_TOKENS = 'PROJECT_APPLICATION_SHARE_TOKENS';
  public const SQL_PROJECT_APPLICATION_PROJECT_NAME = 'PROJECT_APPLICATION_PROJECT_NAME';

  /**
   * @var string
   *
   * Temporary project names start with an uppercase letter, do not contain
   * spaces and end with the year where the project terminates.
   */
  public const TEMPORARY_PROJECT_NAME_REGEXP = '[A-Z]\w+\d{4}';
  /**
   * @var string
   *
   * The project application share token is the sha256 hash of the person's
   * email address expressed as a hex-string in lower case. We enforce lower
   * case hex number in order to distinguish the token from the project name
   * which starts with an upper case letter.
   */
  public const PROJECT_APPLICATION_TOKEN_REGEXP = '[a-f0-9]{64}';
}

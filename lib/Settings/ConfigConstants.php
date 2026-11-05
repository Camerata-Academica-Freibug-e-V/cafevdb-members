<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2025, 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Settings;

/** Define some constants in order to enforce consistency. */
class ConfigConstants
{
  const MEMBER_ROOT_FOLDER_KEY = 'memberRootFolder';
  const FOLDER_GROUPS_KEY = 'memberFolderGroups';
  const SYNCHRONIZE_KEY = 'synchronize';
  const USER_VIEWS_DATABASE_KEY = 'cloudUserViewsDatabase';
  const REGISTRATION_REPLY_TO_KEY = 'registrationReplyTo';

  // Keys of the orchestra app (cafevdb) which are only read, never written.
  const SHARE_OWNER_KEY = 'shareOwner';
  const USER_GROUP_KEY = 'userGroup';
  const ORCHESTRA_NAME_KEY = 'orchestra';
  const ORCHESTRA_LOCALE_KEY = 'orchestraLocale';
  const CONCERTS_CALENDAR_URI = 'concerts';
  const REHEARSALS_CALENDAR_URI = 'rehearsals';

  const SETTINGS_KEYS = [
    self::FOLDER_GROUPS_KEY,
    self::MEMBER_ROOT_FOLDER_KEY,
    self::REGISTRATION_REPLY_TO_KEY,
    self::USER_VIEWS_DATABASE_KEY,
    // self::SYNCHRONIZE_KEY, not a setting, rather an action
  ];
}

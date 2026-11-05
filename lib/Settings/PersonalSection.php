<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2022-2024 Claus-Justus Heine
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

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/** Implementation of personal setting section. */
class PersonalSection implements IIconSection
{
  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private string $appName,
    private IL10N $l,
    private IURLGenerator $urlGenerator,
  ) {
  }
  // phpcs:enable

  /** {@inheritdoc} */
  public function getID()
  {
    return $this->appName;
  }

  /** {@inheritdoc} */
  public function getName()
  {
    return $this->l->t('Camerata DB Members');
  }

  /** {@inheritdoc} */
  public function getPriority()
  {
    return 50;
  }

  /** {@inheritdoc} */
  public function getIcon()
  {
    return $this->urlGenerator->imagePath($this->appName, $this->appName . '.svg');
  }
}

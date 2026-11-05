<?php
/**
 * Member's data base connector for CAFEVDB orchetra management app.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\L10N;

use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Adapter resolving the translator of the orchestra app lazily from the
 * container. Without the orchestra app the identity is returned.
 */
class OrchestraTranslator implements IBidirectionalTranslator
{
  private const SERVICE = 'OCA\\CAFEVDB\\Service\\L10N\\BiDirectionalL10N';

  private ?object $delegate = null;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(private ContainerInterface $appContainer)
  {
  }
  // phpcs:enable

  /** @return null|object */
  private function delegate():?object
  {
    if ($this->delegate === null) {
      try {
        $this->delegate = $this->appContainer->get(self::SERVICE);
      } catch (Throwable $t) {
        return null;
      }
    }
    return $this->delegate;
  }

  /** {@inheritdoc} */
  public function t(string $text):string
  {
    return $this->delegate()?->t($text) ?? $text;
  }

  /** {@inheritdoc} */
  public function backTranslate(string $text):string
  {
    return $this->delegate()?->backTranslate($text) ?? $text;
  }
}

<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025 Claus-Justus Heine
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

namespace OCA\CAFeVDBMembers\Exceptions;

/** Thrown at authentication errors. */
class RegistrationDataMissingException extends Exception
{
  protected array $fields;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $message = '',
    int $code = 0,
    ?Throwable $previous = null,
    null|string|array $fields = null,
  ) {
    parent::__construct($message, $code, $previous);
    $this->fields = is_string($fields) ? [$fields] : $fields;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * Return the name of the missing field.
   *
   * @return null|array
   */
  public function getFields(): ?array
  {
    return $this->fields;
  }
}

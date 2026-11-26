<?php
/**
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright Copyright (c) 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
 */

namespace OCA\CAFeVDBMembers\Model;

use OCP\Share\IAttributes;

/** Store attributes for application-specific shares. */
class ApplicationShareAttributes implements IAttributes
{
  /** @var array<string, array<string, bool|string|array|null>> */
  private array $attributes = [];

  /** {@inheritdoc} */
  public function setAttribute(string $scope, string $key, mixed $value): IAttributes
  {
    $this->attributes[$scope][$key] = $value;
    return $this;
  }

  /** {@inheritdoc} */
  public function getAttribute(string $scope, string $key): mixed
  {
    return $this->attributes[$scope][$key] ?? null;
  }

  /** {@inheritdoc} */
  public function toArray(): array
  {
    $result = [];
    foreach ($this->attributes as $scope => $keys) {
      foreach ($keys as $key => $value) {
        $result[] = [
          'scope' => $scope,
          'key' => $key,
          'value' => $value,
        ];
      }
    }
    return $result;
  }
}

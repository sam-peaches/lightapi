<?php

declare(strict_types = 1);

namespace LightAPI\Database;

final readonly class DOrder {
  public function __construct(private DOrderType $type, private string $columnName) {}

  public function getType(): DOrderType {
    return $this->type;
  }

  public function getColumnName(): string {
    return $this->columnName;
  }
}

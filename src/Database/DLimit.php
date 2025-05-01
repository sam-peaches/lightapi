<?php

declare(strict_types = 1);

namespace LightAPI\Database;

final readonly class DLimit {
  public function __construct(private int $limit = 0, private int $offset = 0) {}

  public function getLimit(): int {
    return $this->limit;
  }

  public function getOffset(): int {
    return $this->offset;
  }
}

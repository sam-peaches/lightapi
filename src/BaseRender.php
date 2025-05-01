<?php

declare(strict_types = 1);

namespace LightAPI;

abstract class BaseRender {
  abstract public function render(array $data): void;
}

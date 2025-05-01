<?php

declare(strict_types = 1);

namespace LightAPI\Error;

final class UnauthorizedError extends BaseError {
  public function __construct() {
    parent::__construct(code: 401, message: 'Unauthorized');
  }
}

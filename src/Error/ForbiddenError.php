<?php

declare(strict_types = 1);

namespace LightAPI\Error;

final class ForbiddenError extends BaseError {
  public function __construct() {
    parent::__construct(code: 403, message: 'Forbidden');
  }
}

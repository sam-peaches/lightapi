<?php

declare(strict_types = 1);

namespace LightAPI\Error;

final class NotFoundError extends BaseError {
  public function __construct() {
    parent::__construct(code: 404, message: 'Not Found');
  }
}

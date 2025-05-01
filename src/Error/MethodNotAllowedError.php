<?php

declare(strict_types = 1);

namespace LightAPI\Error;

use LightAPI\BaseResponse;
use LightAPI\Util\TArray;

final class MethodNotAllowedError extends BaseError {
  public function __construct(array $methods) {
    BaseResponse::setHeader(key: 'Allow', value: TArray::join(array: $methods, separator: ', '));
    parent::__construct(code: 405, message: 'Method Not Allowed');
  }
}

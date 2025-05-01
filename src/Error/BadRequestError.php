<?php

declare(strict_types = 1);

namespace LightAPI\Error;

class BadRequestError extends BaseError {
  final public const int REQUEST_URL_INVALID               = 20101;
  final public const int REQUEST_HEADER_BASIC_AUTH_INVALID = 20111;
  final public const int REQUEST_BODY_MUST_BE_JSON         = 20121;

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function __construct(int $code, array $dump = []) {
    parent::__construct(code: $code, message: static::getMessageByCode(code: $code), dump: $dump);
  }
}

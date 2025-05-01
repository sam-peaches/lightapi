<?php

declare(strict_types = 1);

namespace LightAPI\Util;

use JsonException;
use LightAPI\Error\BadRequestError;
use LightAPI\Error\InternalServerError;
use Throwable;
use function json_decode;
use function json_encode;
use const JSON_BIGINT_AS_STRING;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class JSON {
  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function stringify(array $value): string | null {
    try {
      $json = json_encode(value: $value, flags: JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
      if ($json === false) {
        throw new JsonException();
      }
      return $json;
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::JSON_STRINGIFY_FAIL, dump: ['value' => $value, 'throwable' => $throwable]);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function parse(string $json): array {
    try {
      $value = json_decode(json: $json, associative: true, flags: JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
      if (!TArray::isArray(value: $value)) {
        throw new JsonException();
      }
      return $value;
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::JSON_PARSE_FAIL, dump: ['value' => $json, 'throwable' => $throwable]);
    }
  }
}
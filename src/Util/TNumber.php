<?php

declare(strict_types = 1);

namespace LightAPI\Util;

use function ceil;
use function floor;
use function intval;
use function is_float;
use function is_int;
use function round;
use function strval;
use const PHP_INT_MAX;

final class TNumber {
  final public const int MAX_SAFE_INTEGER = PHP_INT_MAX;

  public static function isInteger(mixed $value): bool {
    return is_int(value: $value);
  }

  public static function isFloat(mixed $value): bool {
    return is_float(value: $value);
  }

  public static function isNumber(mixed $value): bool {
    return self::isInteger(value: $value) || self::isFloat(value: $value);
  }

  public static function toString(int | float $value, int $radix = 10): string {
    if ($radix !== 10) {
      return base_convert(num: strval(value: $value), from_base: 10, to_base: $radix);
    }
    return strval(value: $value);
  }

  public static function floor(int | float $value): int {
    return intval(value: floor(num: $value));
  }

  public static function round(int | float $value): int {
    return intval(value: round(num: $value));
  }

  public static function ceil(int | float $value): int {
    return intval(value: ceil(num: $value));
  }
}

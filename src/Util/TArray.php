<?php

declare(strict_types = 1);

namespace LightAPI\Util;

use function array_key_exists;
use function array_pop;
use function array_push;
use function array_shift;
use function array_unshift;
use function count;
use function implode;
use function in_array;
use function is_array;

final class TArray {
  public static function isArray(mixed $value): bool {
    return is_array(value: $value);
  }

  public static function join(array $array, string $separator): string {
    return implode(separator: $separator, array: $array);
  }

  public static function includes(array $array, mixed $value): bool {
    return in_array(needle: $value, haystack: $array, strict: true);
  }

  public static function contains(array $array, int | string $key): bool {
    return array_key_exists(key: $key, array: $array);
  }

  public static function length(array $array): int {
    return count(value: $array);
  }

  public static function push(array &$array, array $values): void {
    array_push($array, ...$values);
  }

  public static function pop(array &$array): mixed {
    return array_pop(array: $array);
  }

  public static function unshift(array &$array, array $values): void {
    array_unshift($array, ...$values);
  }

  public static function shift(array &$array): mixed {
    return array_shift(array: $array);
  }
}

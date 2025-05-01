<?php

declare(strict_types = 1);

namespace LightAPI\Util;

use LightAPI\Error\InternalServerError;
use ReflectionClass;
use Throwable;
use function gettype;
use function is_bool;
use function is_callable;
use function rawurlencode;

class BaseUtil {
  public static function isBoolean(mixed $value): bool {
    return is_bool(value: $value);
  }

  public static function isCallable(mixed $value): bool {
    return is_callable(value: $value);
  }

  public static function typeof(mixed $value): string {
    return gettype(value: $value);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function getClassConstantNameByValue(string $className, int $value): string {
    try {
      $class = new ReflectionClass(objectOrClass: $className);
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::REFLECTION_INIT_FAIL, dump: ['className' => $className, 'throwable' => $throwable]);
    }
    $constants = $class->getConstants();
    foreach ($constants as $name => $constantValue) {
      if ($constantValue === $value) {
        return $name;
      }
    }
    throw new InternalServerError(code: InternalServerError::REFLECTION_CONSTANT_NOT_EXISTS, dump: ['className' => $className, 'constants' => $constants, 'value' => $value]);
  }

  public static function encodeURIComponent(string $url): string {
    return rawurlencode(string: $url);
  }

  public static function encodeURIPath(string $path): string {
    $paths = TString::split(string: $path, separator: '/');
    foreach ($paths as $key => $name) {
      $paths[$key] = self::encodeURIComponent(url: $name);
    }
    return TArray::join(array: $paths, separator: '/');
  }
}

<?php

declare(strict_types = 1);

namespace LightAPI;

use LightAPI\Error\InternalServerError;
use LightAPI\Util\FileSystem;
use LightAPI\Util\JSON;
use function header;
use function http_response_code;
use function ob_clean;
use function ob_end_clean;
use function ob_end_flush;
use function ob_flush;
use function ob_get_clean;
use function ob_get_contents;
use function ob_get_level;
use function ob_start;
use const PHP_OUTPUT_HANDLER_CLEANABLE;
use const PHP_OUTPUT_HANDLER_FLUSHABLE;
use const PHP_OUTPUT_HANDLER_REMOVABLE;

class BaseResponse {
  final public const int BUFFER_LEVEL_NOT_OPENED = 1;
  final public const int BUFFER_LEVEL_OPENED     = 2;

  final public static function setCodeOk(): void {
    self::setCode(code: 200);
  }

  final public static function setCodeCreated(): void {
    self::setCode(code: 201);
  }

  final public static function setCodeNoContent(): void {
    self::setCode(code: 204);
  }

  final public static function setCodeResetContent(): void {
    self::setCode(code: 205);
  }

  final public static function setCodeBadRequest(): void {
    self::setCode(code: 400);
  }

  final public static function setCodeInternalServerError(): void {
    self::setCode(code: 500);
  }

  final public static function setCode(int $code): void {
    http_response_code(response_code: $code);
  }

  final public static function setHeader(string $key, string $value): void {
    header(header: $key . ': ' . $value);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function sendJSON(array $pre = [], array $data = [], array $post = []): string {
    self::setHeader(key: 'Content-Type', value: 'application/json');
    FileSystem::writeSTDOUT(data: JSON::stringify(value: $data));
    return self::sendEnd();
  }

  final public static function sendRender(BaseRender $render, array $data = []): string {
    self::setHeader(key: 'Content-Type', value: 'text/html');
    $render->render(data: $data);
    return self::sendEnd();
  }

  final public static function sendNoContent(array $pre = []): string {
    self::setCodeNoContent();
    return self::sendEnd();
  }

  final public static function sendEnd(array $pre = []): string {
    return BaseRequest::LAST;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function openBuffer(int $level): void {
    self::checkBuffer(level: $level);
    if (ob_start(flags: PHP_OUTPUT_HANDLER_CLEANABLE | PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE) === false) {
      throw new InternalServerError(code: InternalServerError::RESPONSE_OPEN_BUFFER_FAIL);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function cleanBuffer(int $level): void {
    self::checkBuffer(level: $level);
    if (ob_clean() === false) {
      throw new InternalServerError(code: InternalServerError::RESPONSE_CLEAN_BUFFER_FAIL);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function closeBuffer(int $level): void {
    self::checkBuffer(level: $level);
    if (ob_end_clean() === false) {
      throw new InternalServerError(code: InternalServerError::RESPONSE_CLOSE_BUFFER_FAIL);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function flushBuffer(int $level): void {
    self::checkBuffer(level: $level);
    if (ob_flush() === false) {
      throw new InternalServerError(code: InternalServerError::RESPONSE_FLUSH_BUFFER_FAIL);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function flushAndCloseBuffer(int $level): void {
    self::checkBuffer(level: $level);
    if (ob_end_flush() === false) {
      throw new InternalServerError(code: InternalServerError::RESPONSE_FLUSH_AND_CLOSE_BUFFER_FAIL);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function getBuffer(int $level): string {
    self::checkBuffer(level: $level);
    $buffer = ob_get_contents();
    if ($buffer === false) {
      throw new InternalServerError(code: InternalServerError::RESPONSE_GET_BUFFER_FAIL);
    }
    return $buffer;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function getAndCleanBuffer(int $level): string {
    self::checkBuffer(level: $level);
    $buffer = ob_get_clean();
    if ($buffer === false) {
      throw new InternalServerError(code: InternalServerError::RESPONSE_GET_AND_CLEAN_BUFFER_FAIL);
    }
    return $buffer;
  }

  final public static function getBufferLevel(): int {
    return ob_get_level();
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function checkBuffer(int $level): void {
    if (self::getBufferLevel() !== $level) {
      throw new InternalServerError(code: InternalServerError::RESPONSE_BUFFER_LEVEL_INVALID);
    }
  }
}

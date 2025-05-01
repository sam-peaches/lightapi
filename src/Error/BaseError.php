<?php

declare(strict_types = 1);

namespace LightAPI\Error;

use Exception;
use LightAPI\BaseResponse;
use LightAPI\Util\FileSystem;
use LightAPI\Util\TArray;
use LightAPI\Util\TString;
use LightAPI\Util\BaseUtil;
use Throwable;
use function set_error_handler;

/**
 * @throws \LightAPI\Error\InternalServerError
 */
function handleError(int $errno, string $error, string $file, int $line): never {
  throw new InternalServerError(code: InternalServerError::REQUEST_THROW_HANDLER, dump: ['errno' => $errno, 'error' => $error, 'file' => $file, 'line' => $line]);
}

abstract class BaseError extends Exception {
  final public static function setHandler(): void {
    set_error_handler(callback: 'LightAPI\Error\handleError');
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final protected static function getMessageByCode(int $code): string {
    $values = [];
    try {
      $name = BaseUtil::getClassConstantNameByValue(className: static::class, value: $code);
    } catch (Throwable $throwable) {
      // $name = 'INVALID_CODE_' . $code . '_WITH_ERROR_' . $throwable->getMessage();
      throw new InternalServerError(code: InternalServerError::ERROR_CODE_INVALID, dump: ['code' => $code, 'className' => static ::class, 'throwable' => $throwable]);
    }
    foreach (TString::split(string: $name, separator: '_') as $value) {
      $values[] = TString::toTitleCase(string: $value);
    }
    return TArray::join(array: $values, separator: ' ');
  }

  public function __construct(int $code, string $message, private readonly array $dump = []) {
    parent::__construct(message: $message, code: $code);
  }

  final public function getDump(): array {
    return $this->dump;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function stringifyDump(int $bufferLevel): string {
    BaseResponse::openBuffer(level: $bufferLevel);
    FileSystem::safeWriteSTDERR(data: $this->getDump());
    return 'Fatal error: Uncaught ' . $this->getMessage() . ' (' . $this->getCode() . '): "'
           . TString::replaceRegExpAll(
        string     : BaseResponse::getAndCleanBuffer(level: $bufferLevel + 1),
        pattern    : [
          '/=>\n\s*/',
          '/=> string\(\d+\) "/'
        ],
        replacement: [
          '=> ',
          '=> "'
        ]
      )
           . '" in '
           . $this->getFile()
           . ':'
           . $this->getLine()
           . "\n"
           . 'Stack trace:'
           . "\n"
           . $this->getTraceAsString()
           . "\n"
           . '  thrown in '
           . $this->getFile()
           . ' on line '
           . $this->getLine();
  }

  final public function getResponseCode(): int {
    if ($this instanceof BadRequestError) {
      return 400;
    } elseif ($this instanceof InternalServerError) {
      return 500;
    } else {
      return $this->getCode();
    }
  }

  final public function setResponseCode(): void {
    if ($this instanceof BadRequestError) {
      BaseResponse::setCodeBadRequest();
    } elseif ($this instanceof InternalServerError) {
      BaseResponse::setCodeInternalServerError();
    } else {
      BaseResponse::setCode(code: $this->getCode());
    }
  }
}

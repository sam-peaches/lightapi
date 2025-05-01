<?php

declare(strict_types = 1);

namespace LightAPI;

use LightAPI\Error\BadRequestError;
use LightAPI\Error\BaseError;
use LightAPI\Error\InternalServerError;
use LightAPI\Error\NotFoundError;
use LightAPI\Error\UnauthorizedError;
use LightAPI\Util\Crypto;
use LightAPI\Util\FileSystem;
use LightAPI\Util\JSON;
use LightAPI\Util\TArray;
use LightAPI\Util\TString;
use LightAPI\Util\BaseUtil;
use Throwable;
use function apache_request_headers;
use function parse_url;
use const PHP_URL_PATH;

abstract class BaseRequest {
  public const bool         DEBUG   = false;
  final public const string PATTERN = '?';
  final public const string LAST    = '#';
  final public const string GET     = 'GET';
  final public const string HEAD    = 'HEAD';
  final public const string POST    = 'POST';
  final public const string PUT     = 'PUT';
  final public const string PATCH   = 'PATCH';
  final public const string DELETE  = 'DELETE';
  final public const string OPTIONS = 'OPTIONS';

  private string | null $method;
  private array | null  $paths;
  private array | null  $headers;
  private string | null $body;

  public function __construct() {
    $this->paths  = null;
    $this->method = null;
    $this->body   = null;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function getMethod(): string {
    if ($this->method === null) {
      $method       = self::getServerVariable(name: 'REQUEST_METHOD');
      $this->method = $method;
    }
    return $this->method;
  }

  /**
   * @throws \LightAPI\Error\BadRequestError
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function getPaths(): array {
    if ($this->paths === null) {
      $path = self::getServerVariable(name: 'REQUEST_URI');
      $path = parse_url(url: $path, component: PHP_URL_PATH);
      if (!TString::isString(value: $path)) {
        throw new BadRequestError(code: BadRequestError::REQUEST_URL_INVALID, dump: ['path' => $path]);
      }
      while (TString::includes(string: $path, search: '//')) {
        $path = TString::replace(string: $path, pattern: '//', replacement: '/');
      }
      $pathSeparated = $path;
      if (TString::startsWith(string: $pathSeparated, search: '/')) {
        $pathSeparated = TString::slice(string: $pathSeparated, start: 1);
      }
      if (TString::endWith(string: $pathSeparated, search: '/')) {
        $pathSeparated = TString::slice(string: $pathSeparated, start: 0, end: -1);
      }
      $paths = [];
      if ($path !== '' && $path !== '/') {
        $paths = TString::split(string: $pathSeparated, separator: '/');
      }
      $paths[]     = self::LAST;
      $this->paths = $paths;
    }
    return $this->paths;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function getHeaders(): array {
    if ($this->headers === null) {
      try {
        $rawHeaders = apache_request_headers();
        if ($rawHeaders === false) {
          throw new InternalServerError(code: InternalServerError::REQUEST_GET_HEADERS_FAIL);
        }
      } catch (InternalServerError $error) {
        throw $error;
      } catch (Throwable $throwable) {
        throw new InternalServerError(code: InternalServerError::REQUEST_GET_HEADERS_FAIL, dump: ['throwable' => $throwable]);
      }
      $headers = [];
      foreach ($rawHeaders as $name => $value) {
        $headers[TString::toLowerCase(string: $name)] = $value;
      }
      $this->headers = $headers;
    }
    return $this->headers;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function getBody(): string {
    if ($this->body === null) {
      $body       = FileSystem::readSTDIN();
      $this->body = $body;
    }
    return $this->body;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function getServerVariable(string $name): string {
    if (isset($_SERVER) && TArray::isArray(value: $_SERVER) && TArray::contains(array: $_SERVER, key: $name)) {
      $value = $_SERVER[$name];
      if (!TString::isString(value: $value)) {
        throw new InternalServerError(code: InternalServerError::REQUEST_SERVER_VARIABLE_NOT_EXISTS, dump: ['name' => $name]);
      }
      return $value;
    }
    throw new InternalServerError(code: InternalServerError::REQUEST_SERVER_VARIABLE_NOT_EXISTS, dump: ['name' => $name]);
  }

  final public static function getQueryParams(): array {
    if (isset($_REQUEST) && TArray::isArray(value: $_REQUEST)) {
      return $_REQUEST;
    }
    return [];
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function getOriginName(): string {
    return self::getServerVariable(name: 'REQUEST_SCHEME') . '://' . self::getHostName();
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function getHostName(): string {
    return self::getServerVariable(name: 'HTTP_HOST');
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   * @throws \LightAPI\Error\BadRequestError
   */
  final public function getPathName(): string {
    return '/' . TString::slice(string: TArray::join(array: $this->getPaths(), separator: '/'), start: 0, end: -1);
  }

  /**
   * @throws \LightAPI\Error\UnauthorizedError
   * @throws \LightAPI\Error\BadRequestError
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function getAuthorizationBasic(): array {
    $headers = $this->getHeaders();
    if (!TArray::contains(array: $headers, key: 'authorization')) {
      throw new UnauthorizedError();
    }
    $headerAuthorization = $headers['authorization'];
    if (!TString::startsWith(string: TString::toLowerCase(string: $headerAuthorization), search: 'basic ')) {
      throw new BadRequestError(code: BadRequestError::REQUEST_HEADER_BASIC_AUTH_INVALID);
    }
    try {
      $auths = TString::split(string: Crypto::decodeBase64(string: TString::slice(string: $headerAuthorization, start: 6)), separator: ':');
    } catch (InternalServerError $error) {
      if ($error->getCode() === InternalServerError::CRYPTO_BASE64_DECODE_FAIL) {
        throw new BadRequestError(code: BadRequestError::REQUEST_HEADER_BASIC_AUTH_INVALID);
      }
      throw $error;
    }
    if (TArray::length(array: $auths) !== 2) {
      throw new BadRequestError(code: BadRequestError::REQUEST_HEADER_BASIC_AUTH_INVALID);
    }
    return $auths;
  }

  /**
   * @throws \LightAPI\Error\BadRequestError
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function getBodyJSON(): array {
    try {
      return JSON::parse(json: $this->getBody());
    } catch (InternalServerError $error) {
      if ($error->getCode() === InternalServerError::JSON_PARSE_FAIL) {
        throw new BadRequestError(code: BadRequestError::REQUEST_BODY_MUST_BE_JSON);
      }
      throw $error;
    }
  }

  abstract public function getRouting(): array;

  abstract public function handleError(BaseError $error): void;

  final public function load(): void {
    try {
      $error = $this->tryLoad();
    } catch (InternalServerError $throwable) {
      $error = $throwable;
    }
    if ($error !== null) {
      try {
        BaseResponse::cleanBuffer(level: BaseResponse::BUFFER_LEVEL_OPENED);
        $this->handleError(error: $error);
        BaseResponse::flushAndCloseBuffer(level: BaseResponse::BUFFER_LEVEL_OPENED);
      } catch (Throwable $throwable) {
        BaseResponse::setCodeInternalServerError();
        BaseResponse::setHeader(key: 'Content-Type', value: 'text/plain');
        try {
          BaseResponse::closeBuffer(level: BaseResponse::BUFFER_LEVEL_OPENED);
        } catch (Throwable $throwableInner) {
          if (static::DEBUG) {
            FileSystem::safeWriteSTDERR(
              data: new InternalServerError(
                code: InternalServerError::REQUEST_CLOSE_BUFFER_THROW_FAIL, dump: ['error' => $error, 'throwable' => $throwable, 'throwableInner' => $throwableInner]
              )->stringifyDump(bufferLevel: BaseResponse::BUFFER_LEVEL_NOT_OPENED)
            );
          }
        }
        if (static::DEBUG) {
          FileSystem::safeWriteSTDERR(
            data: new InternalServerError(
              code: InternalServerError::REQUEST_THROW, dump: ['error' => $error, 'throwable' => $throwable]
            )->stringifyDump(bufferLevel: BaseResponse::BUFFER_LEVEL_NOT_OPENED)
          );
        }
      }
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private function tryLoad(): BaseError | null {
    try {
      BaseError::setHandler();
      BaseResponse::openBuffer(level: BaseResponse::BUFFER_LEVEL_NOT_OPENED);
      $this->handlePaths();
      BaseResponse::flushAndCloseBuffer(level: BaseResponse::BUFFER_LEVEL_OPENED);
      return null;
    } catch (BaseError $baseError) {
      return $baseError;
    } catch (Throwable $throwable) {
      return new InternalServerError(code: InternalServerError::REQUEST_LAST_PATH_FUNCTION_THROW, dump: ['throwable' => $throwable]);
    }
  }

  /**
   * @throws \LightAPI\Error\BaseError
   */
  private function handlePaths(): void {
    $paths    = $this->getPaths();
    $routing  = $this->getRouting();
    $patterns = [];
    foreach ($paths as $path) {
      if (TArray::contains(array: $routing, key: $path)) {
        $routePath = $routing[$path];
        if ($path === self::LAST) {
          if (!BaseUtil::isCallable(value: $routePath)) {
            throw new InternalServerError(
              code: InternalServerError::REQUEST_LAST_PATH_FUNCTION_EXPECTED,
              dump: [
                'paths' => $paths,
                'path'  => $path,
                'value' => $routePath,
                'type'  => BaseUtil::typeof(value: $routePath)
              ]
            );
          }
          $last = $routePath($patterns);
          if ($last !== self::LAST) {
            throw new InternalServerError(code: InternalServerError::REQUEST_LAST_PATH_RETURN_EXPECTED, dump: ['paths' => $paths, 'path' => $path, 'returned' => $last]);
          }
          return;
        }
        if (!TArray::isArray(value: $routePath)) {
          throw new InternalServerError(
            code: InternalServerError::REQUEST_ROUTING_ARRAY_EXPECTED,
            dump: [
              'paths' => $paths,
              'path'  => $path,
              'value' => $routePath,
              'type'  => BaseUtil::typeof(value: $routePath)
            ]
          );
        }
        $routing = $routePath;
      } elseif (TArray::contains(array: $routing, key: self::PATTERN)) {
        $patterns[] = $path;
        $routing    = $routing[self::PATTERN];
      } else {
        throw new NotFoundError();
      }
    }
    throw new InternalServerError(code: InternalServerError::REQUEST_LAST_PATH_EXPECTED, dump: ['paths' => $paths]);
  }
}

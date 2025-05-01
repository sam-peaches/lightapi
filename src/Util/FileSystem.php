<?php

declare(strict_types = 1);

namespace LightAPI\Util;

use LightAPI\Error\InternalServerError;
use Throwable;
use function fclose;
use function fopen;
use function fwrite;
use function scandir;
use function stream_get_contents;
use function var_dump;

final class FileSystem {
  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function openStream(string $fileName, FileSystemType $type): mixed {
    try {
      $stream = fopen(
        filename: $fileName,
        mode    : match ($type) {
          FileSystemType::Read  => 'r',
          FileSystemType::Write => 'w',
        }
      );
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_OPEN_FAIL, dump: ['fileName' => $fileName, 'throwable' => $throwable]);
    }
    if ($stream === false) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_OPEN_FAIL, dump: ['fileName' => $fileName]);
    }
    return $stream;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function readStream(mixed $stream): string {
    $data = stream_get_contents(stream: $stream);
    if ($data === false) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_READ_FAIL, dump: ['stream' => $stream]);
    }
    return $data;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function writeStream(mixed $stream, string $data): void {
    try {
      $length = fwrite(stream: $stream, data: $data);
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_WRITE_FAIL, dump: ['stream' => $stream, 'data' => $data, 'throwable' => $throwable]);
    }
    if ($length === false) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_WRITE_FAIL, dump: ['stream' => $stream, 'data' => $data]);
    }
    if (TString::length(string: $data) !== $length) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_WRITE_FAIL, dump: ['stream' => $stream, 'data' => $data, 'length' => $length]);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function closeStream(mixed $stream): void {
    $data = fclose(stream: $stream);
    if ($data === false) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_CLOSE_FAIL, dump: ['stream' => $stream]);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function readFile(string $fileName): string {
    $stream = FileSystem::openStream(fileName: $fileName, type: FileSystemType::Read);
    $data   = FileSystem::readStream(stream: $stream);
    FileSystem::closeStream(stream: $stream);
    return $data;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function writeFile(string $fileName, string $data): void {
    $stream = FileSystem::openStream(fileName: $fileName, type: FileSystemType::Write);
    FileSystem::writeStream(stream: $stream, data: $data);
    FileSystem::closeStream(stream: $stream);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function openSTDIN(): mixed {
    return self::openStream(fileName: 'php://input', type: FileSystemType::Read);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function openSTDOUT(): mixed {
    return self::openStream(fileName: 'php://output', type: FileSystemType::Write);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function readSTDIN(): string {
    return self::readFile(fileName: 'php://input');
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function writeSTDOUT(string $data): void {
    self::writeFile(fileName: 'php://output', data: $data);
  }

  public static function safeWriteSTDERR(mixed $data): void {
    var_dump(value: $data);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function readDir(string $dirName): array {
    try {
      $filenames = scandir(directory: $dirName);
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_READDIR_FAIL, dump: ['dirName' => $dirName, 'throwable' => $throwable]);
    }
    if ($filenames === false) {
      throw new InternalServerError(code: InternalServerError::FILESYSTEM_READDIR_FAIL, dump: ['dirName' => $dirName]);
    }
    $list = [];
    foreach ($filenames as $fileName) {
      if ($fileName !== '.' && $fileName !== '..') {
        $list[] = $fileName;
      }
    }
    return $list;
  }
}

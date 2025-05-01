<?php

declare(strict_types = 1);

namespace LightAPI\Util;

use LightAPI\Error\InternalServerError;
use Throwable;
use function base64_encode;
use function hash;
use function random_bytes;

final class Crypto {
  private const int    RANDOM_LENGTH = 20;
  private const string RANDOM_HASH   = 'sha256';

  public static function encodeBase64(string $string): string {
    return base64_encode(string: $string);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function decodeBase64(string $string): string {
    $data = base64_decode(string: $string, strict: true);
    if ($data === false) {
      throw new InternalServerError(code: InternalServerError::CRYPTO_BASE64_DECODE_FAIL, dump: ['string' => $string]);
    }
    return $data;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function hashPassword(string $password, string | null $salt = null): string {
    if ($salt === null) {
      $salt = self::generateRandomString(length: self::RANDOM_LENGTH);
    }
    return $salt . self::encodeBase64(string: hash(algo: self::RANDOM_HASH, data: $salt . $password, binary: true));
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function checkPassword(string $password, string $hashedPassword): bool {
    return self::hashPassword(password: $password, salt: TString::slice(string: $hashedPassword, start: 0, end: self::RANDOM_LENGTH)) === $hashedPassword;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function generateRandomString(int $length): string {
    if ($length % 4 !== 0 || $length < 1) {
      throw new InternalServerError(code: InternalServerError::CRYPTO_RANDOM_INVALID_LENGTH, dump: ['length' => $length]);
    }
    try {
      $string = TString::replaceAll(string: self::encodeBase64(string: random_bytes(length: $length * 3 / 4)), patterns: ['+', '/'], replacements: ['A', 'B']);
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::CRYPTO_RANDOM_GENERATE_FAIL, dump: ['length' => $length, 'throwable' => $throwable]);
    }
    return TString::replaceAll(
        string      : TString::slice(string: $string, start: 0, end: 1),
        patterns    : ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        replacements: ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j']
      ) . TString::slice(string: $string, start: 1);
  }
}

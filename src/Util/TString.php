<?php

declare(strict_types = 1);

namespace LightAPI\Util;

use function explode;
use function is_string;
use function mb_convert_case;
use function mb_str_pad;
use function mb_str_split;
use function mb_strlen;
use function mb_substr;
use function mb_trim;
use function preg_replace;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use const MB_CASE_LOWER;
use const MB_CASE_TITLE;
use const MB_CASE_UPPER;
use const STR_PAD_LEFT;

final readonly class TString {
  private const string WHITESPACES = "\x09\x0a\x0b\x0c\x0d\x20\x85\xa0\u{1680}\u{2000}\u{2001}\u{2002}\u{2003}\u{2004}\u{2005}\u{2006}\u{2007}\u{2008}\u{2009}\u{200a}\u{2028}\u{2029}\u{202f}\u{205f}\u{3000}";

  public static function isString(mixed $value): bool {
    return is_string(value: $value);
  }

  public static function length(string $string): int {
    return mb_strlen(string: $string);
  }

  public static function split(string $string, string $separator, int $limit = TNumber::MAX_SAFE_INTEGER): array {
    if ($separator === '') {
      return mb_str_split(string: $string);
    }
    return explode(separator: $separator, string: $string, limit: $limit);
  }

  public static function slice(string $string, int $start, int | null $end = null): string {
    if ($end < 0) {
      $length = self::length(string: $string) - $end;
    } elseif ($end === null) {
      $length = null;
    } else {
      $length = $end - $start;
    }
    return mb_substr(string: $string, start: $start, length: $length);
  }

  public static function replace(string $string, string $pattern, string $replacement): string {
    return str_replace(search: $pattern, replace: $replacement, subject: $string);
  }

  public static function replaceAll(string $string, array $patterns, array $replacements): string {
    return str_replace(search: $patterns, replace: $replacements, subject: $string);
  }

  public static function replaceRegExp(string $string, string $pattern, string $replacement): string {
    return preg_replace(pattern: $pattern, replacement: $replacement, subject: $string);
  }

  public static function replaceRegExpAll(string $string, array $pattern, array $replacement): string {
    return preg_replace(pattern: $pattern, replacement: $replacement, subject: $string);
  }

  public static function trim(string $string): string {
    return mb_trim(string: $string, characters: self::WHITESPACES);
  }

  public static function padStart(string $string, int $length, string $padding): string {
    return mb_str_pad(string: $string, length: $length, pad_string: $padding, pad_type: STR_PAD_LEFT);
  }

  public static function padEnd(string $string, int $length, string $padding): string {
    return mb_str_pad(string: $string, length: $length, pad_string: $padding);
  }

  public static function toLowerCase(string $string): string {
    return mb_convert_case(string: $string, mode: MB_CASE_LOWER);
  }

  public static function toUpperCase(string $string): string {
    return mb_convert_case(string: $string, mode: MB_CASE_UPPER);
  }

  public static function toTitleCase(string $string): string {
    return mb_convert_case(string: $string, mode: MB_CASE_TITLE);
  }

  public static function startsWith(string $string, string $search): bool {
    return str_starts_with(haystack: $string, needle: $search);
  }

  public static function endWith(string $string, string $search): bool {
    return str_ends_with(haystack: $string, needle: $search);
  }

  public static function includes(string $string, string $search): bool {
    return str_contains(haystack: $string, needle: $search);
  }
}

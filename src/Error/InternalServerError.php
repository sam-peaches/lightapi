<?php

declare(strict_types = 1);

namespace LightAPI\Error;

class InternalServerError extends BaseError {
  final public const int REQUEST_SERVER_VARIABLE_NOT_EXISTS  = 10101;
  final public const int REQUEST_LAST_PATH_FUNCTION_THROW    = 10102;
  final public const int REQUEST_THROW_HANDLER               = 10103;
  final public const int REQUEST_THROW                       = 10104;
  final public const int REQUEST_LAST_PATH_FUNCTION_EXPECTED = 10105;
  final public const int REQUEST_LAST_PATH_RETURN_EXPECTED   = 10106;
  final public const int REQUEST_ROUTING_ARRAY_EXPECTED      = 10107;
  final public const int REQUEST_LAST_PATH_EXPECTED          = 10108;
  final public const int REQUEST_GET_HEADERS_FAIL            = 10109;

  final public const int RESPONSE_OPEN_BUFFER_FAIL            = 10111;
  final public const int RESPONSE_CLEAN_BUFFER_FAIL           = 10112;
  final public const int RESPONSE_CLOSE_BUFFER_FAIL           = 10113;
  final public const int RESPONSE_FLUSH_BUFFER_FAIL           = 10114;
  final public const int RESPONSE_FLUSH_AND_CLOSE_BUFFER_FAIL = 10115;
  final public const int RESPONSE_GET_BUFFER_FAIL             = 10116;
  final public const int RESPONSE_GET_AND_CLEAN_BUFFER_FAIL   = 10117;
  final public const int RESPONSE_BUFFER_LEVEL_INVALID        = 10118;

  final public const int REQUEST_CLOSE_BUFFER_THROW_FAIL = 10121;
  final public const int ERROR_CODE_INVALID              = 10129;

  final public const int JSON_STRINGIFY_FAIL = 10201;
  final public const int JSON_PARSE_FAIL     = 10202;

  final public const int CRYPTO_RANDOM_INVALID_LENGTH = 10211;
  final public const int CRYPTO_RANDOM_GENERATE_FAIL  = 10212;
  final public const int CRYPTO_BASE64_DECODE_FAIL    = 10213;

  final public const int FILESYSTEM_OPEN_FAIL    = 10221;
  final public const int FILESYSTEM_READ_FAIL    = 10222;
  final public const int FILESYSTEM_WRITE_FAIL   = 10223;
  final public const int FILESYSTEM_CLOSE_FAIL   = 10224;
  final public const int FILESYSTEM_READDIR_FAIL = 10225;

  final public const int REFLECTION_INIT_FAIL           = 10231;
  final public const int REFLECTION_CONSTANT_NOT_EXISTS = 10232;

  final public const int MODEL_NOT_CONTAINS_COLUMN = 10301;

  final public const int DATABASE_VALUE_TYPE_UNSUPPORTED                    = 10311;
  final public const int DATABASE_ASSIGNMENT_KEY_MUST_BE_STRING             = 10312;
  final public const int DATABASE_USERNAME_MUST_BE_STRING                   = 10313;
  final public const int DATABASE_COLUMN_NAME_MUST_BE_STRING                = 10314;
  final public const int DATABASE_CONDITION_KEY_MUST_BE_STRING_OR_CONDITION = 10315;
  final public const int DATABASE_INSERT_MULTIPLE_MUST_BE_ARRAY             = 10316;

  final public const int DATABASE_FIND_ONE_RETURNS_MORE_ONE_RESULT = 10321;
  final public const int DATABASE_CREATE_ID_EXPECTED               = 10322;
  final public const int DATABASE_CREATE_AFFECTED_ROW_INVALID      = 10323;
  final public const int DATABASE_UPDATE_AFFECTED_ROW_INVALID      = 10324;
  final public const int DATABASE_DELETE_AFFECTED_ROW_INVALID      = 10325;

  final public const int DATABASE_INIT_FAIL                     = 10331;
  final public const int DATABASE_SET_INT_OPTION_FAIL           = 10332;
  final public const int DATABASE_CONNECT_FAIL                  = 10333;
  final public const int DATABASE_SET_CHARSET_FAIL              = 10334;
  final public const int DATABASE_CONNECT_THROW                 = 10335;
  final public const int DATABASE_REQUEST_QUERY_FAIL            = 10336;
  final public const int DATABASE_REQUEST_QUERY_THROW           = 10337;
  final public const int DATABASE_REQUEST_NON_NULLABLE_EXPECTED = 10338;
  final public const int DATABASE_REQUEST_NULLABLE_EXPECTED     = 10339;
  final public const int DATABASE_STORE_RESULTS_FAIL            = 10340;
  final public const int DATABASE_STORE_RESULTS_THROW           = 10341;
  final public const int DATABASE_WARNING_CONTAIN               = 10342;
  final public const int DATABASE_WARNING_THROW                 = 10343;

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public function __construct(int $code, array $dump = []) {
    parent::__construct(code: $code, message: static::getMessageByCode(code: $code), dump: $dump);
  }
}

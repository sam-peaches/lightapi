<?php

/** @noinspection PhpClassHasTooManyDeclaredMembersInspection */

declare(strict_types = 1);

namespace LightAPI\Database\MySQL;

use LightAPI\Database\DLimit;
use LightAPI\Database\DOrder;
use LightAPI\Database\DOrderType;
use LightAPI\Error\InternalServerError;
use LightAPI\Util\TArray;
use LightAPI\Util\TNumber;
use LightAPI\Util\TString;
use LightAPI\Util\TUndefined;
use LightAPI\Util\BaseUtil;

final class DatabaseMySQLQuery {
  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function select(string $databaseName, string $tableName, array $columnList, array $conditionMap): string {
    $stringifyColumnList = '*';
    if (TArray::length(array: $columnList) !== 0) {
      $stringifyColumnList = self::stringifyColumnList(databaseName: $databaseName, tableName: $tableName, columnList: $columnList);
    }
    return 'SELECT ' . $stringifyColumnList . ' FROM ' . self::stringifyTableName(databaseName: $databaseName, tableName: $tableName) . self::stringifyConditionMap(
        databaseName: $databaseName,
        tableName   : $tableName,
        conditionMap: $conditionMap
      );
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyColumnList(string $databaseName, string $tableName, array $columnList): string {
    $list = [];
    foreach ($columnList as $columnName) {
      if (!TString::isString(value: $columnName)) {
        throw new InternalServerError(code: InternalServerError::DATABASE_COLUMN_NAME_MUST_BE_STRING, dump: ['columnName' => $columnName]);
      }
      $list[] = self::stringifyColumnName(databaseName: $databaseName, tableName: $tableName, columnName: $columnName);
    }
    return self::stringifyList(list: $list);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function insertList(string $databaseName, string $tableName, array $assignmentMapList): string {
    return 'INSERT ' . self::stringifyTableName(databaseName: $databaseName, tableName: $tableName) . self::stringifyColumnListList(
        databaseName     : $databaseName,
        tableName        : $tableName,
        assignmentMapList: $assignmentMapList
      ) . ' VALUES' . self::stringifyValueListList(assignmentMapList: $assignmentMapList);
  }

  private static function sortColumnList($assignmentMapList): array {
    $list = [];
    foreach ($assignmentMapList as $assignmentMap) {
      foreach ($assignmentMap as $assignmentKey => $ignored) {
        if (!TArray::includes(array: $list, value: $assignmentKey)) {
          $list[] = $assignmentKey;
        }
      }
    }
    return $list;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyColumnListList(string $databaseName, string $tableName, array $assignmentMapList): string {
    return self::stringifyWrap(
      expression: self::stringifyColumnList(
        databaseName: $databaseName,
        tableName   : $tableName,
        columnList  : self::sortColumnList(assignmentMapList: $assignmentMapList)
      )
    );
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyValueList(array $assignmentMap, array $sortColumnList): string {
    $list = [];
    foreach ($sortColumnList as $columnName) {
      $find = false;
      foreach ($assignmentMap as $assignmentKey => $assignmentValue) {
        if ($assignmentKey === $columnName && !$assignmentValue instanceof TUndefined) {
          $list[] = self::stringifyValue(value: $assignmentValue);
          $find   = true;
          break;
        }
      }
      if (!$find) {
        $list[] = self::stringifyValue(value: null);
      }
    }
    return self::stringifyWrap(expression: self::stringifyList(list: $list));
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyValueListList(array $assignmentMapList): string {
    $sortColumnList = self::sortColumnList(assignmentMapList: $assignmentMapList);
    $list           = [];
    foreach ($assignmentMapList as $assignmentMap) {
      if (!TArray::isArray(value: $assignmentMap)) {
        throw new InternalServerError(code: InternalServerError::DATABASE_INSERT_MULTIPLE_MUST_BE_ARRAY, dump: ['assignmentMap' => $assignmentMap]);
      }
      $list[] = self::stringifyValueList(assignmentMap: $assignmentMap, sortColumnList: $sortColumnList);
    }
    return self::stringifyList(list: $list);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function insert(string $databaseName, string $tableName, array $assignmentMap): string {
    return 'INSERT ' . self::stringifyTableName(databaseName: $databaseName, tableName: $tableName) . ' SET ' . self::stringifyAssignmentMap(
        databaseName : $databaseName,
        tableName    : $tableName,
        assignmentMap: $assignmentMap
      );
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function update(string $databaseName, string $tableName, array $assignmentMap, array $conditionMap): string {
    return 'UPDATE ' . self::stringifyTableName(databaseName: $databaseName, tableName: $tableName) . ' SET ' . self::stringifyAssignmentMap(
        databaseName : $databaseName,
        tableName    : $tableName,
        assignmentMap: $assignmentMap
      ) . self::stringifyConditionMap(databaseName: $databaseName, tableName: $tableName, conditionMap: $conditionMap);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyAssignmentMap(string $databaseName, string $tableName, array $assignmentMap): string {
    $list = [];
    foreach ($assignmentMap as $assignmentKey => $assigmentValue) {
      if (!$assigmentValue instanceof TUndefined) {
        if (!TString::isString(value: $assignmentKey)) {
          throw new InternalServerError(code: InternalServerError::DATABASE_ASSIGNMENT_KEY_MUST_BE_STRING, dump: ['assignmentKey' => $assignmentKey]);
        }
        $list[] = self::stringifyAssignment(databaseName: $databaseName, tableName: $tableName, columnName: $assignmentKey, value: $assigmentValue);
      }
    }
    return self::stringifyList(list: $list);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function delete(string $databaseName, string $tableName, array $conditionMap): string {
    return 'DELETE FROM ' . self::stringifyTableName(databaseName: $databaseName, tableName: $tableName) . self::stringifyConditionMap(
        databaseName: $databaseName,
        tableName   : $tableName,
        conditionMap: $conditionMap
      );
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyConditionMap(string $databaseName, string $tableName, array $conditionMap): string {
    $where = '';
    $order = '';
    $limit = '';
    $list  = [];
    foreach ($conditionMap as $conditionKey => $conditionValue) {
      if (!$conditionValue instanceof TUndefined) {
        if ($conditionValue instanceof DOrder) {
          $order = ' ORDER BY '
                   . self::stringifyColumnName(databaseName: $databaseName, tableName: $tableName, columnName: $conditionValue->getColumnName())
                   . ' '
                   . match ($conditionValue->getType()) {
                     DOrderType::Ascending  => 'ASC',
                     DOrderType::Descending => 'DESC',
                   };
        } elseif ($conditionValue instanceof DLimit) {
          $limit = ' LIMIT ' . $conditionValue->getLimit() . ' OFFSET ' . $conditionValue->getOffset();
        } elseif (TString::isString(value: $conditionKey)) {
          $listValues = [];
          if (TArray::isArray(value: $conditionValue)) {
            foreach ($conditionValue as $value) {
              $listValues[] = self::stringifyValue(value: $value);
            }
            $list[] = self::stringifyColumnName(databaseName: $databaseName, tableName: $tableName, columnName: $conditionKey) . ' IN ' . self::stringifyWrap(
                expression: self::stringifyList(list: $listValues)
              );
          } else {
            $list[] = self::stringifyAssignment(databaseName: $databaseName, tableName: $tableName, columnName: $conditionKey, value: $conditionValue);
          }
        } else {
          throw new InternalServerError(code: InternalServerError::DATABASE_CONDITION_KEY_MUST_BE_STRING_OR_CONDITION, dump: [$conditionKey => $conditionValue]);
        }
      }
    }
    if (TArray::length(array: $list) !== 0) {
      $where = ' WHERE ' . TArray::join(array: $list, separator: ' AND ');
    }
    return $where . $order . $limit;
  }

  public static function createDatabase(string $databaseName): string {
    return 'CREATE DATABASE '
           . self::stringifyDatabaseName(databaseName: $databaseName)
           . '  CHARACTER SET '
           . BaseDatabaseMySQL::CHARSET
           . ' COLLATE '
           . BaseDatabaseMySQL::COLLATE;
  }

  public static function dropDatabase(string $databaseName): string {
    return 'DROP DATABASE ' . self::stringifyDatabaseName(databaseName: $databaseName);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function createUser(string $userName, string $password): string {
    return 'CREATE USER ' . self::stringifyUserName(userName: $userName) . ' IDENTIFIED BY ' . self::stringifyValue(value: $password);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function dropUserList(array $userList): string {
    return 'DROP USER ' . self::stringifyUserList(userList: $userList);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyUserList(array $userList): string {
    $list = [];
    foreach ($userList as $userName) {
      if (!TString::isString(value: $userName)) {
        throw new InternalServerError(code: InternalServerError::DATABASE_USERNAME_MUST_BE_STRING, dump: ['userName' => $userName]);
      }
      $list[] = self::stringifyUserName(userName: $userName);
    }
    return self::stringifyList(list: $list);
  }

  private static function stringifyColumnName(string $databaseName, string $tableName, string $columnName): string {
    return self::stringifyTableName(databaseName: $databaseName, tableName: $tableName) . '.' . self::stringifyKeyName(keyName: $columnName);
  }

  private static function stringifyTableName(string $databaseName, string $tableName): string {
    return self::stringifyDatabaseName(databaseName: $databaseName) . '.' . self::stringifyKeyName(keyName: $tableName);
  }

  private static function stringifyDatabaseName(string $databaseName): string {
    return self::stringifyKeyName(keyName: $databaseName);
  }

  private static function stringifyUserName(string $userName): string {
    return '`' . self::escapeKey(string: $userName) . '`';
  }

  private static function stringifyKeyName(string $keyName): string {
    return '`' . self::escapeKey(string: $keyName) . '`';
  }

  private static function stringifyList(array $list): string {
    return TArray::join(array: $list, separator: ', ');
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyAssignment(string $databaseName, string $tableName, string $columnName, mixed $value): string {
    return self::stringifyColumnName(databaseName: $databaseName, tableName: $tableName, columnName: $columnName) . '=' . self::stringifyValue(value: $value);
  }

  private static function stringifyWrap(string $expression): string {
    return '(' . $expression . ')';
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function stringifyValue(mixed $value): string {
    if ($value === null) {
      return 'NULL';
    } elseif (TString::isString(value: $value)) {
      return '\'' . self::escape(string: $value) . '\'';
    } elseif (TNumber::isNumber(value: $value)) {
      return TNumber::toString(value: $value);
    } elseif (BaseUtil::isBoolean(value: $value)) {
      if ($value) {
        return '1';
      }
      return '0';
    }
    throw new InternalServerError(code: InternalServerError::DATABASE_VALUE_TYPE_UNSUPPORTED, dump: ['value' => $value, 'type' => BaseUtil::typeof(value: $value)]);
  }

  private static function escape(string $string): string {
    return TString::replaceAll(string: $string, patterns: ["\0", "\n", "\r", '\\', '\'', '"', "\x1a"], replacements: ['\0', '\n', '\r', '\\\\', '\\\'', '\"', '\Z']);
  }

  private static function escapeKey(string $string): string {
    return TString::replaceAll(string: $string, patterns: ["\0", "\n", "\r", '\\', '\'', '"', "\x1a", '`'], replacements: ['\0', '\n', '\r', '\\\\', '\\\'', '\"', '\Z', '\`']);
  }
}

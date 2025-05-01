<?php

declare(strict_types = 1);

namespace LightAPI\Database\MySQL;

use LightAPI\Database\BaseDatabase;
use LightAPI\Error\InternalServerError;
use LightAPI\Util\TArray;
use LightAPI\Util\TUndefined;
use mysqli;
use Override;
use Throwable;
use function mysqli_init;
use function mysqli_report;
use const MYSQLI_ASSOC;
use const MYSQLI_OPT_INT_AND_FLOAT_NATIVE;
use const MYSQLI_REPORT_ALL;
use const MYSQLI_REPORT_INDEX;

abstract class BaseDatabaseMySQL implements BaseDatabase {
  private const string      HOST    = '127.0.0.1';
  final public const string CHARSET = 'utf8mb4';
  final public const string COLLATE = 'utf8mb4_0900_ai_ci';

  private mysqli | null $internal     = null;
  private string | int  $insertID     = 0;
  private int           $affectedRows = 0;

  abstract public static function getDatabaseName(): string;

  abstract public static function getUsername(): string;

  abstract public static function getPassword(): string;

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function findAll(string $table, array $columns, array $conditions): array {
    return $this->request(
      query     : DatabaseMySQLQuery::select(databaseName: static::getDatabaseName(), tableName: $table, columnList: $columns, conditionMap: $conditions),
      isNullable: false
    );
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function findOne(string $table, array $columns, array $conditions): array | null {
    $list = $this->request(
      query     : DatabaseMySQLQuery::select(databaseName: static::getDatabaseName(), tableName: $table, columnList: $columns, conditionMap: $conditions),
      isNullable: false
    );
    return match (TArray::length(array: $list)) {
      0       => null,
      1       => $list[0],
      default => throw new InternalServerError(
        code: InternalServerError::DATABASE_FIND_ONE_RETURNS_MORE_ONE_RESULT,
        dump: [
          'table'      => $table,
          'conditions' => $conditions,
          'results'    => $list
        ]
      )
    };
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function createList(string $table, array $assignmentsList): void {
    $this->request(query: DatabaseMySQLQuery::insertList(databaseName: static::getDatabaseName(), tableName: $table, assignmentMapList: $assignmentsList), isNullable: true);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function createOne(string $table, array $assignments): void {
    $this->request(query: DatabaseMySQLQuery::insert(databaseName: static::getDatabaseName(), tableName: $table, assignmentMap: $assignments), isNullable: true);
    $this->checkAffectedOne(code: InternalServerError::DATABASE_CREATE_AFFECTED_ROW_INVALID, assignments: $assignments);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function createAndFindList(string $table, array $columns, array $assignmentsList, string $primaryColumn): array {
    $list = [];
    foreach ($assignmentsList as $assignmentMap) {
      $list[] = self::createAndFindOne(table: $table, columns: $columns, assignments: $assignmentMap, primaryColumn: $primaryColumn);
    }
    return $list;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function createAndFindOne(string $table, array $columns, array $assignments, string $primaryColumn): array {
    $this->createOne(table: $table, assignments: $assignments);
    if ($this->insertID === 0) {
      throw new InternalServerError(code: InternalServerError::DATABASE_CREATE_ID_EXPECTED, dump: ['table' => $table, 'assignments' => $assignments]);
    }
    return self::findOne(table: $table, columns: $columns, conditions: [$primaryColumn => $this->insertID]);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final  public function updateAll(string $table, array $assignments, array $conditions): void {
    $this->request(
      query     : DatabaseMySQLQuery::update(databaseName: static::getDatabaseName(), tableName: $table, assignmentMap: $assignments, conditionMap: $conditions),
      isNullable: true
    );
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function updateOne(string $table, array $assignments, array $conditions): void {
    if (!self::checkZeroSized(assignmentMap: $assignments)) {
      $this->request(
        query     : DatabaseMySQLQuery::update(databaseName: static::getDatabaseName(), tableName: $table, assignmentMap: $assignments, conditionMap: $conditions),
        isNullable: true
      );
      $this->checkAffectedOne(code: InternalServerError::DATABASE_UPDATE_AFFECTED_ROW_INVALID, allowZero: true, assignments: $assignments, conditions: $conditions);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function updateAndFindAll(string $table, array $columns, array $assignments, array $conditions): array {
    $this->request(
      query     : DatabaseMySQLQuery::update(databaseName: static::getDatabaseName(), tableName: $table, assignmentMap: $assignments, conditionMap: $conditions),
      isNullable: true
    );
    return self::findAll(table: $table, columns: $columns, conditions: $conditions);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function updateAndFindOne(string $table, array $columns, array $assignments, array $conditions): array | null {
    $this->updateOne(table: $table, assignments: $assignments, conditions: $conditions);
    return self::findOne(table: $table, columns: $columns, conditions: $conditions);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function deleteAll(string $table, array $conditions): void {
    $this->request(query: DatabaseMySQLQuery::delete(databaseName: static::getDatabaseName(), tableName: $table, conditionMap: $conditions), isNullable: true);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function deleteOne(string $table, array $conditions): void {
    $this->request(query: DatabaseMySQLQuery::delete(databaseName: static::getDatabaseName(), tableName: $table, conditionMap: $conditions), isNullable: true);
    $this->checkAffectedOne(code: InternalServerError::DATABASE_DELETE_AFFECTED_ROW_INVALID, conditions: $conditions);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function deleteAndFindAll(string $table, array $columns, array $conditions): array {
    $model = self::findAll(table: $table, columns: $columns, conditions: $conditions);
    $this->request(query: DatabaseMySQLQuery::delete(databaseName: static::getDatabaseName(), tableName: $table, conditionMap: $conditions), isNullable: true);
    return $model;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function deleteAndFindOne(string $table, array $columns, array $conditions): array | null {
    $model = self::findOne(table: $table, columns: $columns, conditions: $conditions);
    if ($model === null) {
      return null;
    }
    $this->deleteOne(table: $table, conditions: $conditions);
    return $model;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function createDatabase(string $database): void {
    $this->request(query: DatabaseMySQLQuery::createDatabase(databaseName: $database), isNullable: true);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function deleteDatabase(string $database): void {
    $this->request(query: DatabaseMySQLQuery::dropDatabase(databaseName: $database), isNullable: true);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function createUser(string $username, string $password): void {
    $this->request(query: DatabaseMySQLQuery::createUser(userName: $username, password: $password), isNullable: true);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  #[Override] final public function deleteUser(string $username): void {
    $this->request(query: DatabaseMySQLQuery::dropUserList(userList: [$username]), isNullable: true);
  }

  private static function checkZeroSized(array $assignmentMap): bool {
    foreach ($assignmentMap as $assignmentValue) {
      if (!$assignmentValue instanceof TUndefined) {
        return false;
      }
    }
    return true;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private function checkAffectedOne(int $code, bool $allowZero = false, array $assignments = [], array $conditions = []): void {
    if ($this->affectedRows !== 1 && (!$allowZero || $this->affectedRows !== 0)) {
      throw new InternalServerError(code: $code, dump: ['affectedRows' => $this->affectedRows, 'assignments' => $assignments, 'conditions' => $conditions]);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private function request(string $query, bool $isNullable): array | null {
    $currentError = null;
    try {
      if ($this->internal === null) {
        $this->internal = self::connect();
      }
      [$this->insertID, $this->affectedRows] = self::saveData(internal: $this->internal, query: $query);
      return self::handleData(internal: $this->internal, query: $query, isNullable: $isNullable);
    } catch (InternalServerError $error) {
      $currentError = $error;
      throw $currentError;
    } finally {
      if ($this->internal !== null) {
        self::checkStack(internal: $this->internal, query: $query, currentError: $currentError);
      }
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function checkStack(mysqli $internal, string $query, InternalServerError | null $currentError): void {
    $stack = $internal->error_list;
    if ($internal->connect_errno !== 0) {
      $stack[] = [$internal->connect_errno, $internal->connect_error];
    }
    if ($internal->warning_count !== 0) {
      for ($count = 0, $warnCount = $internal->warning_count; $count < $warnCount; $count++) {
        try {
          $stack[] = $internal->get_warnings();
        } catch (Throwable $throwable) {
          throw new InternalServerError(
            code: InternalServerError::DATABASE_WARNING_THROW,
            dump: [
              'stack'     => $stack,
              'query'     => $query,
              'previous'  => $currentError,
              'throwable' => $throwable
            ]
          );
        }
      }
    }
    if (TArray::length(array: $stack) !== 0) {
      throw new InternalServerError(code: InternalServerError::DATABASE_WARNING_CONTAIN, dump: ['stack' => $stack, 'query' => $query, 'previous' => $currentError]);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function connect(): mysqli {
    try {
      mysqli_report(flags: MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_INDEX);
      $mysqli = mysqli_init();
      if ($mysqli === false) {
        throw new InternalServerError(code: InternalServerError::DATABASE_INIT_FAIL);
      }
      if (!$mysqli->options(option: MYSQLI_OPT_INT_AND_FLOAT_NATIVE, value: true)) {
        throw new InternalServerError(code: InternalServerError::DATABASE_SET_INT_OPTION_FAIL);
      }
      if (!$mysqli->real_connect(hostname: self::HOST, username: static::getUsername(), password: static::getPassword(), database: static::getDatabaseName())) {
        throw new InternalServerError(code: InternalServerError::DATABASE_CONNECT_FAIL);
      }
      if (!$mysqli->set_charset(charset: self::CHARSET)) {
        throw new InternalServerError(code: InternalServerError::DATABASE_SET_CHARSET_FAIL);
      }
      return $mysqli;
    } catch (InternalServerError $error) {
      throw $error;
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::DATABASE_CONNECT_THROW, dump: ['throwable' => $throwable]);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function saveData(mysqli $internal, string $query): array {
    try {
      if (!$internal->real_query(query: $query)) {
        throw new InternalServerError(code: InternalServerError::DATABASE_REQUEST_QUERY_FAIL, dump: ['query' => $query]);
      }
      return [$internal->insert_id, $internal->affected_rows];
    } catch (InternalServerError $error) {
      throw $error;
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::DATABASE_REQUEST_QUERY_THROW, dump: ['query' => $query, 'throwable' => $throwable]);
    }
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function handleData(mysqli $internal, string $query, bool $isNullable): array | null {
    try {
      $result = $internal->store_result();
      if ($result === false) {
        if ($internal->error === '' && $internal->errno === 0 && $internal->field_count === 0) {
          if ($isNullable) {
            return null;
          }
          throw new InternalServerError(code: InternalServerError::DATABASE_REQUEST_NON_NULLABLE_EXPECTED, dump: ['query' => $query]);
        }
        throw new InternalServerError(code: InternalServerError::DATABASE_STORE_RESULTS_FAIL, dump: ['query' => $query]);
      }
      if ($isNullable) {
        throw new InternalServerError(code: InternalServerError::DATABASE_REQUEST_NULLABLE_EXPECTED, dump: ['query' => $query, 'result' => $result]);
      }
      $data = $result->fetch_all(mode: MYSQLI_ASSOC);
      $result->free();
      return $data;
    } catch (InternalServerError $error) {
      throw $error;
    } catch (Throwable $throwable) {
      throw new InternalServerError(code: InternalServerError::DATABASE_STORE_RESULTS_THROW, dump: ['query' => $query, 'throwable' => $throwable]);
    }
  }
}

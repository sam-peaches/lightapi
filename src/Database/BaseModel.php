<?php

/** @noinspection PhpClassHasTooManyDeclaredMembersInspection */

declare(strict_types = 1);

namespace LightAPI\Database;

use LightAPI\Error\InternalServerError;
use LightAPI\Error\NotFoundError;
use LightAPI\Util\TArray;

abstract class BaseModel {
  abstract public static function getTable(): string;

  abstract public static function getPrimaryColumn(): string;

  abstract public static function getColumns(): array;

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function filterColumnAll(array $dataList): array {
    $list = [];
    foreach ($dataList as $data) {
      $list[] = static::filterColumnOne(data: $data);
    }
    return $list;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  private static function filterColumnOne(array | null $data): array | null {
    if ($data === null) {
      return null;
    }
    $list = [];
    foreach (static::getColumns() as $columnName) {
      if (TArray::contains(array: $data, key: $columnName)) {
        $list[$columnName] = $data[$columnName];
      } else {
        throw new InternalServerError(code: InternalServerError::MODEL_NOT_CONTAINS_COLUMN, dump: ['data' => $data, 'columnName' => $columnName, 'className' => static::class]);
      }
    }
    return $list;
  }

  /**
   * @throws \LightAPI\Error\NotFoundError
   */
  private static function getNonNull(array | null $data): array {
    if ($data === null) {
      throw new NotFoundError();
    }
    return $data;
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function findAll(BaseDatabase $database, array $conditions): array {
    return static::filterColumnAll(dataList: $database->findAll(table: static::getTable(), columns: static::getColumns(), conditions: $conditions));
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  public static function findOne(BaseDatabase $database, array $conditions): array | null {
    return static::filterColumnOne(data: $database->findOne(table: static::getTable(), columns: static::getColumns(), conditions: $conditions));
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   * @throws \LightAPI\Error\NotFoundError
   */
  public static function findOneNonNull(BaseDatabase $database, array $conditions): array {
    return self::getNonNull(data: static::filterColumnOne(data: $database->findOne(table: static::getTable(), columns: static::getColumns(), conditions: $conditions)));
  }

  final public static function createList(BaseDatabase $database, array $assignmentMapList): void {
    $database->createList(table: static::getTable(), assignmentsList: $assignmentMapList);
  }

  final public static function createOne(BaseDatabase $database, array $assignments): void {
    $database->createOne(table: static::getTable(), assignments: $assignments);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function createAndFindList(BaseDatabase $database, array $assignmentMapList): array {
    return static::filterColumnAll(
      dataList: $database->createAndFindList(
        table          : static::getTable(),
        columns        : static::getColumns(),
        assignmentsList: $assignmentMapList,
        primaryColumn  : static::getPrimaryColumn()
      )
    );
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function createAndFindOne(BaseDatabase $database, array $assignments): array {
    return static::filterColumnOne(
      data: $database->createAndFindOne(
        table        : static::getTable(),
        columns      : static::getColumns(),
        assignments  : $assignments,
        primaryColumn: static::getPrimaryColumn()
      )
    );
  }

  final public static function updateList(BaseDatabase $database, array $assignmentMap, array $conditions): void {
    $database->updateAll(table: static::getTable(), assignments: $assignmentMap, conditions: $conditions);
  }

  final public static function updateOne(BaseDatabase $database, array $assignments, array $conditions): void {
    $database->updateOne(table: static::getTable(), assignments: $assignments, conditions: $conditions);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function updateAndFindList(BaseDatabase $db, array $assignmentMap, array $conditions): array {
    return static::filterColumnAll(dataList: $db->updateAndFindAll(table: static::getTable(), columns: static::getColumns(), assignments: $assignmentMap, conditions: $conditions));
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function updateAndFindOne(BaseDatabase $database, array $assignments, array $conditions): array | null {
    return static::filterColumnOne(data: $database->updateAndFindOne(table: static::getTable(), columns: static::getColumns(), assignments: $assignments, conditions: $conditions));
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   * @throws \LightAPI\Error\NotFoundError
   */
  final public static function updateAndFindOneNonNull(BaseDatabase $database, array $assignments, array $conditions): array {
    return self::getNonNull(
      data: static::filterColumnOne(data: $database->updateAndFindOne(table: static::getTable(), columns: static::getColumns(), assignments: $assignments, conditions: $conditions))
    );
  }

  final public static function deleteList(BaseDatabase $database, array $conditions): void {
    $database->deleteAll(table: static::getTable(), conditions: $conditions);
  }

  final public static function deleteOne(BaseDatabase $database, array $conditions): void {
    $database->deleteOne(table: static::getTable(), conditions: $conditions);
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function deleteAndFindList(BaseDatabase $db, array $conditions): array {
    return static::filterColumnAll(dataList: $db->deleteAndFindAll(table: static::getTable(), columns: static::getColumns(), conditions: $conditions));
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   */
  final public static function deleteAndFindOne(BaseDatabase $database, array $conditions): array | null {
    return static::filterColumnOne(data: $database->deleteAndFindOne(table: static::getTable(), columns: static::getColumns(), conditions: $conditions));
  }

  /**
   * @throws \LightAPI\Error\InternalServerError
   * @throws \LightAPI\Error\NotFoundError
   */
  final public static function deleteAndFindOneNonNull(BaseDatabase $database, array $conditions): array | null {
    return self::getNonNull(data: static::filterColumnOne(data: $database->deleteAndFindOne(table: static::getTable(), columns: static::getColumns(), conditions: $conditions)));
  }
}

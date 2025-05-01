<?php

declare(strict_types = 1);

namespace LightAPI\Database;

interface BaseDatabase {
  public function findAll(string $table, array $columns, array $conditions): array;

  public function findOne(string $table, array $columns, array $conditions): array | null;

  public function createList(string $table, array $assignmentsList): void;

  public function createOne(string $table, array $assignments): void;

  public function createAndFindList(string $table, array $columns, array $assignmentsList, string $primaryColumn): array;

  public function createAndFindOne(string $table, array $columns, array $assignments, string $primaryColumn): array;

  public function updateAll(string $table, array $assignments, array $conditions): void;

  public function updateOne(string $table, array $assignments, array $conditions): void;

  public function updateAndFindAll(string $table, array $columns, array $assignments, array $conditions): array;

  public function updateAndFindOne(string $table, array $columns, array $assignments, array $conditions): array | null;

  public function deleteAll(string $table, array $conditions): void;

  public function deleteOne(string $table, array $conditions): void;

  public function deleteAndFindAll(string $table, array $columns, array $conditions): array;

  public function deleteAndFindOne(string $table, array $columns, array $conditions): array | null;

  public function createDatabase(string $database): void;

  public function deleteDatabase(string $database): void;

  public function createUser(string $username, string $password): void;

  public function deleteUser(string $username): void;
}

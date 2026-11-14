<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Thin PDO wrapper. Every query is a prepared statement with bound values.
 * Table and column names passed to insert()/update()/delete() come from code
 * only (never from user input) and are validated as plain identifiers.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                (string) config('db.host', 'localhost'),
                (int) config('db.port', 3306),
                (string) config('db.name', '')
            );
            self::$pdo = new PDO($dsn, (string) config('db.user', ''), (string) config('db.pass', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            // Keep MySQL NOW() in the same time zone as PHP (value comes from PHP, not users).
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    /** @param array<int|string, mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : ':' . ltrim($key, ':');
            if (is_bool($value)) {
                $stmt->bindValue($name, (int) $value, PDO::PARAM_INT);
            } elseif (is_int($value)) {
                $stmt->bindValue($name, $value, PDO::PARAM_INT);
            } elseif ($value === null) {
                $stmt->bindValue($name, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($name, (string) $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        return $stmt;
    }

    /** @param array<int|string, mixed> $params @return array<int, array<string, mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param array<int|string, mixed> $params @return array<string, mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<int|string, mixed> $params @return mixed */
    public static function value(string $sql, array $params = [])
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /** @param array<string, mixed> $data */
    public static function insert(string $table, array $data): int
    {
        $columns = array_map([self::class, 'identifier'], array_keys($data));
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::identifier($table),
            implode(', ', $columns),
            implode(', ', array_fill(0, count($data), '?'))
        );
        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $where */
    public static function update(string $table, array $data, array $where): int
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = self::identifier($column) . ' = ?';
        }
        [$whereSql, $whereParams] = self::where($where);
        $sql = sprintf('UPDATE %s SET %s WHERE %s', self::identifier($table), implode(', ', $set), $whereSql);
        return self::run($sql, array_merge(array_values($data), $whereParams))->rowCount();
    }

    /** @param array<string, mixed> $where */
    public static function delete(string $table, array $where): int
    {
        [$whereSql, $params] = self::where($where);
        return self::run(sprintf('DELETE FROM %s WHERE %s', self::identifier($table), $whereSql), $params)->rowCount();
    }

    /** @template T @param callable(): T $callback @return T */
    public static function transaction(callable $callback)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @param array<string, mixed> $where @return array{0:string, 1:array<int, mixed>} */
    private static function where(array $where): array
    {
        if ($where === []) {
            throw new \InvalidArgumentException('Refusing to run UPDATE/DELETE without a WHERE clause.');
        }
        $parts = [];
        foreach (array_keys($where) as $column) {
            $parts[] = self::identifier($column) . ' = ?';
        }
        return [implode(' AND ', $parts), array_values($where)];
    }

    private static function identifier(string $name): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException('Invalid SQL identifier: ' . $name);
        }
        return '`' . $name . '`';
    }
}

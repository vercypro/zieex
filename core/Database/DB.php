<?php
declare(strict_types=1);

namespace Zieex\Database;

class DB
{
    private static ?\PDO $connection = null;
    private static string $driver    = 'mysql';

    public static function connect(): \PDO
    {
        if (self::$connection) {
            return self::$connection;
        }

        self::$driver = env('DB_DRIVER', 'mysql');

        $dsn = match (self::$driver) {
            'mysql'  => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', '127.0.0.1'),
                env('DB_PORT', '3306'),
                env('DB_NAME', 'zieex')
            ),
            'pgsql'  => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                env('DB_HOST', '127.0.0.1'),
                env('DB_PORT', '5432'),
                env('DB_NAME', 'zieex')
            ),
            'sqlite' => 'sqlite:' . BASE_PATH . '/storage/' . env('DB_NAME', 'database.sqlite'),
            default  => throw new \RuntimeException('Unsupported DB driver: ' . self::$driver),
        };

        self::$connection = new \PDO(
            $dsn,
            env('DB_USER', 'root'),
            env('DB_PASS', ''),
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        return self::$connection;
    }

    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder(self::connect(), $table);
    }

    public static function raw(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function beginTransaction(): void
    {
        self::connect()->beginTransaction();
    }

    public static function commit(): void
    {
        self::connect()->commit();
    }

    public static function rollback(): void
    {
        self::connect()->rollBack();
    }

    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }
    }

    public static function lastInsertId(): string
    {
        return self::connect()->lastInsertId();
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }
}

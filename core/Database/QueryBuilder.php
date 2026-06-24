<?php
declare(strict_types=1);

namespace Zieex\Database;

class QueryBuilder
{
    private \PDO    $pdo;
    private string  $table;
    private array   $wheres    = [];
    private array   $bindings  = [];
    private array   $selects   = ['*'];
    private ?int    $limitVal  = null;
    private ?int    $offsetVal = null;
    private array   $orders    = [];
    private array   $joins     = [];

    public function __construct(\PDO $pdo, string $table)
    {
        $this->pdo   = $pdo;
        $this->table = $table;
    }

    public function select(string ...$columns): static
    {
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        if ($value === null) {
            [$operator, $value] = ['=', $operatorOrValue];
        } else {
            $operator = $operatorOrValue;
        }

        $this->wheres[]   = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->wheres[] = "{$column} IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->wheres[] = "{$column} IS NOT NULL";
        return $this;
    }

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        if ($value === null) {
            [$operator, $value] = ['=', $operatorOrValue];
        } else {
            $operator = $operatorOrValue;
        }

        $last = array_pop($this->wheres) ?? '1=1';
        $this->wheres[]   = "({$last} OR {$column} {$operator} ?)";
        $this->bindings[] = $value;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limitVal = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offsetVal = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql  = $this->buildSelect();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function find(int|string $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, $id)->first();
    }

    public function count(): int
    {
        $this->selects = ['COUNT(*) as aggregate'];
        $result = $this->first();
        return (int) ($result['aggregate'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function insert(array $data): string
    {
        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(',', $columns),
            implode(',', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function insertGetId(array $data): string
    {
        return $this->insert($data);
    }

    public function update(array $data): int
    {
        $set = implode(',', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $whereClause = $this->buildWhere();
        $sql  = "UPDATE {$this->table} SET {$set}" . ($whereClause ? " WHERE {$whereClause}" : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([...array_values($data), ...$this->bindings]);
        return $stmt->rowCount();
    }

    public function delete(): int
    {
        $whereClause = $this->buildWhere();
        $sql  = "DELETE FROM {$this->table}" . ($whereClause ? " WHERE {$whereClause}" : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->rowCount();
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total   = $this->count();
        $results = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return [
            'data'          => $results,
            'total'         => $total,
            'per_page'      => $perPage,
            'current_page'  => $page,
            'last_page'     => (int) ceil($total / $perPage),
            'has_more'      => ($page * $perPage) < $total,
        ];
    }

    private function buildSelect(): string
    {
        $sql = 'SELECT ' . implode(',', $this->selects) . ' FROM ' . $this->table;

        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $whereClause = $this->buildWhere();
        if ($whereClause) {
            $sql .= " WHERE {$whereClause}";
        }

        if ($this->orders) {
            $sql .= ' ORDER BY ' . implode(',', $this->orders);
        }

        if ($this->limitVal !== null) {
            $sql .= " LIMIT {$this->limitVal}";
        }

        if ($this->offsetVal !== null) {
            $sql .= " OFFSET {$this->offsetVal}";
        }

        return $sql;
    }

    private function buildWhere(): string
    {
        return implode(' AND ', $this->wheres);
    }
}

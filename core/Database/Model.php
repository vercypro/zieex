<?php
declare(strict_types=1);

namespace Zieex\Database;

abstract class Model
{
    protected static string $table      = '';
    protected static string $primaryKey = 'id';
    protected static bool   $timestamps = true;
    protected array $attributes         = [];
    protected array $hidden             = ['password'];
    protected array $casts              = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $this->castAttribute($key, $value);
        }
        return $this;
    }

    private function castAttribute(string $key, mixed $value): mixed
    {
        return match ($this->casts[$key] ?? null) {
            'int', 'integer' => (int) $value,
            'float'          => (float) $value,
            'bool', 'boolean'=> (bool) $value,
            'array'          => is_string($value) ? json_decode($value, true) : $value,
            'json'           => is_string($value) ? json_decode($value, true) : $value,
            default          => $value,
        };
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        $data = $this->attributes;
        foreach ($this->hidden as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    public static function table(): QueryBuilder
    {
        return DB::table(static::$table ?: self::guessTableName());
    }

    private static function guessTableName(): string
    {
        $class = (new \ReflectionClass(static::class))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . 's';
    }

    public static function find(int|string $id): ?static
    {
        $row = static::table()->find($id, static::$primaryKey);
        return $row ? new static($row) : null;
    }

    public static function findOrFail(int|string $id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new \RuntimeException(static::class . " not found with id={$id}");
        }
        return $model;
    }

    public static function all(): array
    {
        return array_map(fn($row) => new static($row), static::table()->get());
    }

    public static function where(string $col, mixed $op, mixed $val = null): QueryBuilder
    {
        return static::table()->where($col, $op, $val);
    }

    public static function create(array $data): static
    {
        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }

        $id = static::table()->insert($data);
        $data[static::$primaryKey] = $id;
        return new static($data);
    }

    public function save(): bool
    {
        if (static::$timestamps) {
            $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        if (isset($this->attributes[static::$primaryKey])) {
            $id = $this->attributes[static::$primaryKey];
            $data = $this->attributes;
            unset($data[static::$primaryKey]);
            static::table()->where(static::$primaryKey, $id)->update($data);
        } else {
            if (static::$timestamps) {
                $this->attributes['created_at'] = date('Y-m-d H:i:s');
            }
            $id = static::table()->insert($this->attributes);
            $this->attributes[static::$primaryKey] = $id;
        }

        return true;
    }

    public function delete(): bool
    {
        $id = $this->attributes[static::$primaryKey] ?? null;
        if (!$id) {
            return false;
        }
        static::table()->where(static::$primaryKey, $id)->delete();
        return true;
    }

    public static function count(): int
    {
        return static::table()->count();
    }
}

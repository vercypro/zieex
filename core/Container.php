<?php
declare(strict_types=1);

namespace Zieex;

class Container
{
    private array $bindings   = [];
    private array $singletons = [];
    private array $instances  = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract, array $params = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = ($this->singletons[$abstract])($this, $params);
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this, $params);
        }

        return $this->resolve($abstract, $params);
    }

    private function resolve(string $class, array $params = []): mixed
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Cannot resolve [{$class}]: class not found.");
        }

        $ref        = new \ReflectionClass($class);
        $constructor = $ref->getConstructor();

        if (!$constructor) {
            return new $class();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (isset($params[$param->getName()])) {
                $args[] = $params[$param->getName()];
            } else {
                throw new \RuntimeException("Cannot resolve parameter [{$param->getName()}] in {$class}");
            }
        }

        return $ref->newInstanceArgs($args);
    }
}

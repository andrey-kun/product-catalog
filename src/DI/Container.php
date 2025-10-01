<?php

namespace App\DI;

class Container
{
    /**
     * @var array
     */
    private $bindings = [];

    /**
     * @var array
     */
    private $instances = [];

    /**
     * Регистрация привязки
     *
     * @param string $abstract
     * @param callable|null $concrete
     * @return void
     */
    public function set(string $abstract, ?callable $concrete = null)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Получение экземпляра объекта
     *
     * @param string $abstract
     * @return mixed
     */
    public function get(string $abstract)
    {
        // Если уже существует экземпляр, возвращаем его
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Получение конкретной привязки
        if (!isset($this->bindings[$abstract])) {
            throw new \InvalidArgumentException("Class {$abstract} is not bound.");
        }

        $concrete = $this->bindings[$abstract];

        // Если это замыкание, вызываем его
        if (is_callable($concrete)) {
            $instance = $concrete($this);
        } else {
            // Создаем новый экземпляр класса
            $instance = new $concrete();
        }

        // Сохраняем экземпляр для одиночного использования (singleton)
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Проверка наличия привязки
     *
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * Регистрация singleton привязки
     *
     * @param string $abstract
     * @param callable|null $concrete
     * @return void
     */
    public function singleton(string $abstract, ?callable $concrete = null)
    {
        $this->set($abstract, function ($container) use ($concrete, $abstract) {
            return $container->get($abstract);
        });
    }

    /**
     * Разрешение зависимостей для конструктора
     *
     * @param string $class
     * @return object
     */
    public function resolve(string $class)
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \InvalidArgumentException("Class {$class} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type !== null && !$type->isBuiltin()) {
                $dependencyClass = $type->getName();
                $dependencies[] = $this->get($dependencyClass);
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \InvalidArgumentException(
                        "Cannot resolve parameter {$parameter->getName()} for {$class}"
                    );
                }
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
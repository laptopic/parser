<?php

namespace App\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements ContainerInterface
{
    protected const SHARED = 'shared';

    protected array $shared = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        if (isset($config[static::SHARED])) {
            foreach ($config[static::SHARED] as $value) {
                $this->shared[$value] = null;
            }
            unset($config[static::SHARED]);
        }
        $this->config = $config;
    }

    public function setConfig(string $id, $array){
        switch( true ) {
            case is_array($array):
            case count($array):
            case is_string($id):
                return $this->config[$id] = $array;
        }

        throw new NotFoundException("Failed to add value");
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id): bool
    {
        return array_key_exists($id, $this->config) || class_exists($id);
    }

    /**
     * @param string $id
     * @return mixed
     * @throws NotFoundException
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->config)) {
            return $this->load($id);
        }
        if (class_exists($id)) {
            return $this->build($id);
        }
        throw new NotFoundException("$id not found");
    }

    /**
     * @param string $id
     * @return mixed
     */
    protected function load(string $id)
    {
        if (!is_callable($this->config[$id])) {
            return $this->config[$id];
        }
        if (isset($this->shared[$id])) {
            return $this->shared[$id];
        }
        if (array_key_exists($id, $this->shared)) {
            $this->shared[$id] = call_user_func($this->config[$id], $this);
            return $this->shared[$id];
        }
        return call_user_func($this->config[$id], $this);
    }

    /**
     * @param string $id
     * @param array  $params
     * @return mixed
     * @throws NotFoundException
     */
    public function build(string $id, array $params = [])
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $class = new ReflectionClass($id);
        $constructor = $class->getConstructor();
        $args = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $p) {
                if (isset($params[$p->getName()])) {
                    $args[] = $params[$p->getName()];
                    continue;
                }
                if ($p->isOptional()) {
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $args[] = $p->getDefaultValue();
                    continue;
                }
                $paramClass = $p->getClass();
                $args[] = $this->get($paramClass ? $paramClass->getName() : $p->getName());
            }
        }
        return $class->newInstanceArgs($args);
    }
}

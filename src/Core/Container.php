<?php

namespace ONToolkit\Core;

if (!defined('ABSPATH')) {
    exit;
}

use Exception;
use InvalidArgumentException;

/**
 * Lightweight PSR-11 inspired Dependency Injection Container.
 */
class Container
{
    private static ?Container $instance = null;
    private array $services = [];
    private array $instances = [];

    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a service factory or class name.
     * @param string $id
     * @param callable|string|object $concrete
     */
    public function set(string $id, $concrete): void
    {
        $this->services[$id] = $concrete;
        unset($this->instances[$id]);
    }

    /**
     * Get a service by ID.
     * @param string $id
     * @return mixed
     */
    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->services[$id])) {
            if (class_exists($id)) {
                $this->instances[$id] = new $id();
                return $this->instances[$id];
            }
            throw new InvalidArgumentException("Service not found in container: {$id}");
        }

        $concrete = $this->services[$id];

        if (is_callable($concrete)) {
            $object = $concrete($this);
        } elseif (is_object($concrete)) {
            $object = $concrete;
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $object = new $concrete();
        } else {
            throw new Exception("Invalid service binding for {$id}");
        }

        $this->instances[$id] = $object;
        return $object;
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->instances[$id]) || class_exists($id);
    }
}

<?php

namespace ONToolkit\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lightweight Dependency Injection Container for ON Toolkit.
 */
class Container
{
    private static ?Container $instance = null;

    /**
     * @var array<string, callable>
     */
    private array $services = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    private function __construct() {}

    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function bind(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->services[$id])) {
            throw new \RuntimeException(sprintf('Service %s not found in container.', $id));
        }

        $this->instances[$id] = call_user_func($this->services[$id], $this);
        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->instances[$id]);
    }
}

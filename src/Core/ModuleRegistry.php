<?php

namespace ONToolkit\Core;

/**
 * Module Registry & Lazy-Loader.
 */
class ModuleRegistry
{
    /**
     * Registered module instances.
     * @var array<string, ModuleInterface>
     */
    private array $modules = [];

    /**
     * Register a module with the registry.
     */
    public function register(ModuleInterface $module): void
    {
        $this->modules[$module->getId()] = $module;
    }

    /**
     * Boot all active modules. Disabled modules load ZERO hooks/assets.
     */
    public function bootActiveModules(): void
    {
        foreach ($this->modules as $module) {
            if ($module->isEnabled()) {
                $module->boot();
            }
        }
    }

    /**
     * Get all registered modules.
     * @return array<string, ModuleInterface>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Get a specific module by ID.
     */
    public function getModule(string $id): ?ModuleInterface
    {
        return $this->modules[$id] ?? null;
    }
}

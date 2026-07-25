<?php

namespace ONToolkit\Core;

/**
 * Interface defining the contract for all ON Toolkit modules.
 */
interface ModuleInterface
{
    /**
     * Unique key for the module (e.g. 'link_scanner', 'media_inspector', 'db_cleaner').
     */
    public function getId(): string;

    /**
     * Human-readable module name.
     */
    public function getName(): string;

    /**
     * Module description.
     */
    public function getDescription(): string;

    /**
     * Check if module is currently enabled.
     */
    public function isEnabled(): bool;

    /**
     * Register module hooks if enabled.
     */
    public function boot(): void;

    /**
     * Array of REST API endpoints exposed by this module.
     */
    public function getRestControllers(): array;
}

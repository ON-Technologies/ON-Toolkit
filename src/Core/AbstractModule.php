<?php

namespace ONToolkit\Core;

/**
 * Base class for ON Toolkit modules.
 */
abstract class AbstractModule implements ModuleInterface
{
    protected bool $enabled = true;

    public function isEnabled(): bool
    {
        $active_modules = get_option('ontk_active_modules', [
            'link_scanner' => true,
            'media_inspector' => true,
            'db_cleaner' => true,
        ]);

        return isset($active_modules[$this->getId()]) ? (bool)$active_modules[$this->getId()] : true;
    }

    public function getRestControllers(): array
    {
        return [];
    }
}

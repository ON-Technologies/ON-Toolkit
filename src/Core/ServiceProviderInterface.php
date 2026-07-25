<?php

namespace ONToolkit\Core;

/**
 * Interface for module service providers.
 */
interface ServiceProviderInterface
{
    /**
     * Register services into the DI container.
     */
    public function register(Container $container): void;

    /**
     * Boot registered services and attach WordPress hooks.
     */
    public function boot(Container $container): void;
}

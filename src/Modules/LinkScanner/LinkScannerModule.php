<?php

namespace ONToolkit\Modules\LinkScanner;

use ONToolkit\Core\AbstractModule;
use ONToolkit\Modules\LinkScanner\Rest\LinkScannerController;

class LinkScannerModule extends AbstractModule
{
    public function getId(): string
    {
        return 'link_scanner';
    }

    public function getName(): string
    {
        return __('Broken Link Scanner', 'on-toolkit');
    }

    public function getDescription(): string
    {
        return __('Automatically detect broken internal/external links, redirects, and timeouts across post content and Elementor.', 'on-toolkit');
    }

    public function boot(): void
    {
        $backgroundScanner = new \ONToolkit\Modules\LinkScanner\Services\BackgroundScanner();
        $backgroundScanner->boot();

        add_action('rest_api_init', function () {
            $controller = new LinkScannerController();
            $controller->register_routes();
        });
    }

    public function getRestControllers(): array
    {
        return [
            new LinkScannerController(),
        ];
    }
}

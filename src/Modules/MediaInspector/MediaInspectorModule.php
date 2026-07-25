<?php

namespace ONToolkit\Modules\MediaInspector;

use ONToolkit\Core\AbstractModule;
use ONToolkit\Modules\MediaInspector\Services\UsageDetector;
use ONToolkit\Modules\MediaInspector\Rest\MediaInspectorController;

class MediaInspectorModule extends AbstractModule
{
    private UsageDetector $usageDetector;

    public function __construct()
    {
        $this->usageDetector = new UsageDetector();
    }

    public function getId(): string
    {
        return 'media_inspector';
    }

    public function getName(): string
    {
        return __('Media Inspector', 'on-toolkit');
    }

    public function getDescription(): string
    {
        return __('Complete visibility into Media Library usage, unused files, missing ALT texts, and file dimensions.', 'on-toolkit');
    }

    public function boot(): void
    {
        add_action('rest_api_init', function () {
            $controller = new MediaInspectorController($this->usageDetector);
            $controller->register_routes();
        });
    }

    public function getRestControllers(): array
    {
        return [
            new MediaInspectorController($this->usageDetector),
        ];
    }
}

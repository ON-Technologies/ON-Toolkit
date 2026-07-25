<?php

namespace ONToolkit\Modules\LinkScanner;

if (!defined('ABSPATH')) {
    exit;
}

use ONToolkit\Core\AbstractModule;
use ONToolkit\Core\Rest\RestController;
use ONToolkit\Modules\LinkScanner\Crawler\PostCrawler;
use ONToolkit\Modules\LinkScanner\Crawler\MenuCrawler;
use ONToolkit\Modules\LinkScanner\Services\HttpVerifier;
use ONToolkit\Modules\LinkScanner\Repositories\LinkRepository;
use ONToolkit\Modules\LinkScanner\Services\BackgroundScanner;
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
        $postCrawler = new PostCrawler();
        $menuCrawler = new MenuCrawler();
        $httpVerifier = new HttpVerifier();
        $linkRepository = new LinkRepository();

        $backgroundScanner = new BackgroundScanner($postCrawler, $menuCrawler, $httpVerifier, $linkRepository);
        $backgroundScanner->initHooks();

        add_action('rest_api_init', function () use ($linkRepository, $backgroundScanner) {
            $controller = new LinkScannerController($linkRepository, $backgroundScanner);
            $controller->register_routes();
        });
    }

    /**
     * @return array<int, RestController>
     */
    public function getRestControllers(): array
    {
        $postCrawler = new PostCrawler();
        $menuCrawler = new MenuCrawler();
        $httpVerifier = new HttpVerifier();
        $linkRepository = new LinkRepository();

        $backgroundScanner = new BackgroundScanner($postCrawler, $menuCrawler, $httpVerifier, $linkRepository);

        return [
            new LinkScannerController($linkRepository, $backgroundScanner),
        ];
    }
}

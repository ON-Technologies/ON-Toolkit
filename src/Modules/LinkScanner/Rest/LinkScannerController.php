<?php

namespace ONToolkit\Modules\LinkScanner\Rest;

use ONToolkit\Core\Rest\RestController;
use ONToolkit\Modules\LinkScanner\Crawler\PostCrawler;
use ONToolkit\Modules\LinkScanner\Crawler\MenuCrawler;
use ONToolkit\Modules\LinkScanner\Services\HttpVerifier;
use ONToolkit\Modules\LinkScanner\Repositories\LinkRepository;
use WP_REST_Request;
use WP_REST_Response;

class LinkScannerController extends RestController
{
    private PostCrawler $postCrawler;
    private MenuCrawler $menuCrawler;
    private HttpVerifier $httpVerifier;
    private LinkRepository $linkRepository;

    public function __construct()
    {
        $this->postCrawler = new PostCrawler();
        $this->menuCrawler = new MenuCrawler();
        $this->httpVerifier = new HttpVerifier();
        $this->linkRepository = new LinkRepository();
    }

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/link-scanner/links', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getLinks'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);

        register_rest_route($this->namespace, '/link-scanner/scan-post', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'scanPost'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);

        register_rest_route($this->namespace, '/link-scanner/start-scan', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'startFullScan'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);

        register_rest_route($this->namespace, '/link-scanner/scan-status', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getScanStatus'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);

        register_rest_route($this->namespace, '/link-scanner/batch-fix', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'batchFixLinks'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);
    }

    public function batchFixLinks(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $action = sanitize_text_field($request->get_param('action') ?? 'ignore');
        $ids = array_map('absint', (array)($request->get_param('ids') ?? []));

        if (empty($ids)) {
            return $this->respondError('No link IDs provided', 'ontk_missing_ids', 400);
        }

        $table_links = "{$wpdb->prefix}ontk_links";
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        if ($action === 'delete') {
            $wpdb->query($wpdb->prepare("DELETE FROM {$table_links} WHERE id IN ({$placeholders})", $ids));
        } elseif ($action === 'ignore') {
            $wpdb->query($wpdb->prepare("UPDATE {$table_links} SET status_type = 'ok' WHERE id IN ({$placeholders})", $ids));
        }

        return $this->respondSuccess([
            'action' => $action,
            'processed_count' => count($ids),
        ]);
    }

    public function startFullScan(WP_REST_Request $request): WP_REST_Response
    {
        $scanner = new \ONToolkit\Modules\LinkScanner\Services\BackgroundScanner();
        $result = $scanner->startFullScan();
        return $this->respondSuccess($result);
    }

    public function getScanStatus(WP_REST_Request $request): WP_REST_Response
    {
        $scanner = new \ONToolkit\Modules\LinkScanner\Services\BackgroundScanner();
        $status = $scanner->getScanStatus();
        return $this->respondSuccess($status);
    }

    public function getLinks(WP_REST_Request $request): WP_REST_Response
    {
        $status_type = sanitize_text_field($request->get_param('status_type') ?? 'all');
        $limit = (int)($request->get_param('limit') ?? 50);
        $offset = (int)($request->get_param('offset') ?? 0);
        $search = sanitize_text_field($request->get_param('search') ?? '');

        $result = $this->linkRepository->getLinks($status_type, $limit, $offset, $search);
        return $this->respondSuccess($result);
    }

    public function scanPost(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = (int)$request->get_param('post_id');

        if (!$post_id) {
            return $this->respondError('Missing post_id', 'ontk_missing_id', 400);
        }

        $urls = $this->postCrawler->extractUrlsFromPost($post_id);
        $scanned_results = [];

        foreach ($urls as $url) {
            $verification = $this->httpVerifier->checkUrl($url);
            $link_id = $this->linkRepository->saveLink($verification);
            $post_type = get_post_type($post_id) ?: 'post';
            $this->linkRepository->saveOccurrence($link_id, $post_id, $post_type, 'content');
            $scanned_results[] = $verification;
        }

        return $this->respondSuccess([
            'post_id' => $post_id,
            'urls_found' => count($urls),
            'scanned' => $scanned_results,
        ]);
    }
}

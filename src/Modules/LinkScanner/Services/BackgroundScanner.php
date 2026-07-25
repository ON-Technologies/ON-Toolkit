<?php

namespace ONToolkit\Modules\LinkScanner\Services;

if (!defined('ABSPATH')) {
    exit;
}

use ONToolkit\Modules\LinkScanner\Crawler\PostCrawler;
use ONToolkit\Modules\LinkScanner\Crawler\MenuCrawler;
use ONToolkit\Modules\LinkScanner\Repositories\LinkRepository;

/**
 * Handles non-blocking background link scanning via Action Scheduler or WP-Cron fallback.
 */
class BackgroundScanner
{
    private PostCrawler $postCrawler;
    private MenuCrawler $menuCrawler;
    private HttpVerifier $httpVerifier;
    private LinkRepository $linkRepository;

    public function __construct(
        PostCrawler $postCrawler,
        MenuCrawler $menuCrawler,
        HttpVerifier $httpVerifier,
        LinkRepository $linkRepository
    ) {
        $this->postCrawler = $postCrawler;
        $this->menuCrawler = $menuCrawler;
        $this->httpVerifier = $httpVerifier;
        $this->linkRepository = $linkRepository;
    }

    public function initHooks(): void
    {
        add_action('ontk_process_link_scan_batch', [$this, 'processBatch']);
    }

    /**
     * Start background scan by scheduling initial batch safely via Action Scheduler or WP-Cron fallback.
     */
    public function dispatchScan(int $batch_offset = 0): void
    {
        update_option('ontk_scan_status', [
            'status' => 'running',
            'scanned_posts' => $batch_offset,
            'total_posts' => $this->getTotalPostCount(),
            'started_at' => current_time('mysql'),
        ]);

        $args = [$batch_offset];

        // Safe Action Scheduler / WooCommerce check with seamless WP-Cron fallback
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action('ontk_process_link_scan_batch', $args, 'on-toolkit');
        } else {
            if (!wp_next_scheduled('ontk_process_link_scan_batch', $args)) {
                wp_schedule_single_event(time(), 'ontk_process_link_scan_batch', $args);
            }
        }
    }

    /**
     * Process 20-post micro-batch in background without blocking admin interface.
     */
    public function processBatch(int $batch_offset = 0): void
    {
        $limit = 20;
        $args = [
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'offset'         => $batch_offset,
            'fields'         => 'ids',
        ];

        /** @var array<int, int> $post_ids */
        $post_ids = get_posts($args);
        $total_posts = $this->getTotalPostCount();

        if (empty($post_ids)) {
            // Crawl Nav Menus as final step
            $menu_links = $this->menuCrawler->extractMenuUrls();
            foreach ($menu_links as $link) {
                /** @var string $url */
                $url = $link['url'] ?? '';
                /** @var array<string, mixed> $occurrence */
                $occurrence = $link['occurrence'] ?? [];
                $check = $this->httpVerifier->checkUrl($url);
                $this->linkRepository->saveLink($check, [$occurrence]);
            }

            update_option('ontk_scan_status', [
                'status' => 'completed',
                'scanned_posts' => $total_posts,
                'total_posts' => $total_posts,
                'completed_at' => current_time('mysql'),
            ]);
            return;
        }

        foreach ($post_ids as $post_id) {
            $extracted_links = $this->postCrawler->extractUrlsFromPost((int)$post_id);
            foreach ($extracted_links as $url => $occurrences) {
                $check = $this->httpVerifier->checkUrl((string)$url);
                $this->linkRepository->saveLink($check, $occurrences);
            }
        }

        $next_offset = $batch_offset + count($post_ids);

        if ($next_offset < $total_posts) {
            $this->dispatchScan($next_offset);
        } else {
            update_option('ontk_scan_status', [
                'status' => 'completed',
                'scanned_posts' => $total_posts,
                'total_posts' => $total_posts,
                'completed_at' => current_time('mysql'),
            ]);
        }
    }

    public function getTotalPostCount(): int
    {
        $counts = wp_count_posts('post');
        $page_counts = wp_count_posts('page');
        return (int)($counts->publish ?? 0) + (int)($page_counts->publish ?? 0);
    }
}

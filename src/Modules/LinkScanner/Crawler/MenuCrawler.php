<?php

namespace ONToolkit\Modules\LinkScanner\Crawler;

/**
 * Extracts links from WordPress navigation menus.
 */
class MenuCrawler
{
    /**
     * Extract URLs from all registered nav menu items.
     */
    public function extractMenuUrls(): array
    {
        $urls = [];
        $menus = wp_get_nav_menus();

        foreach ($menus as $menu) {
            $items = wp_get_nav_menu_items($menu->term_id);
            if (!empty($items)) {
                foreach ($items as $item) {
                    if (!empty($item->url) && (strncmp($item->url, 'http://', 7) === 0 || strncmp($item->url, 'https://', 8) === 0)) {
                        $urls[] = [
                            'url' => strtok(trim($item->url), '#'),
                            'menu_id' => $menu->term_id,
                            'item_id' => $item->ID,
                            'title' => $item->title,
                        ];
                    }
                }
            }
        }

        return $urls;
    }
}

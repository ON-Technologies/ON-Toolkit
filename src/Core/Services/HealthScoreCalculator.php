<?php

namespace ONToolkit\Core\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gamified Site Health Calculator with agency-customizable penalty weight rules.
 */
class HealthScoreCalculator
{
    /**
     * Calculate 0-100 score and sub-category pillars with configurable penalty weights.
     *
     * @return array<string, mixed>
     */
    public function calculateScore(): array
    {
        global $wpdb;

        /** @var array<string, int> $settings */
        $settings = (array)get_option('ontk_settings', []);
        $weights = [
            'link_penalty'         => (int)($settings['link_penalty'] ?? 5),
            'missing_alt_penalty'  => (int)($settings['missing_alt_penalty'] ?? 3),
            'unused_media_penalty' => (int)($settings['unused_media_penalty'] ?? 1),
            'db_waste_penalty'     => (int)($settings['db_waste_penalty'] ?? 15),
        ];

        // 1. Broken Links Pillar (0-100)
        $table_links = "{$wpdb->prefix}ontk_links";
        $broken_count = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_links}'") === $table_links) {
            $broken_count = (int)$wpdb->get_var("SELECT COUNT(id) FROM {$table_links} WHERE status_type = 'broken'");
        }
        $links_score = max(0, 100 - ($broken_count * $weights['link_penalty']));

        // 2. Media Library Pillar (0-100)
        $missing_alt = (int)$wpdb->get_var(
            "SELECT COUNT(p.ID) FROM {$wpdb->posts} p 
             LEFT JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt') 
             WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%' 
             AND (pm.meta_value IS NULL OR TRIM(pm.meta_value) = '')"
        );
        $media_score = max(0, 100 - ($missing_alt * $weights['missing_alt_penalty']));

        // 3. Database Health Pillar (0-100)
        $revisions = (int)$wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        $transients = (int)$wpdb->get_var(
            "SELECT COUNT(option_name) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_%' 
             AND option_value < " . time()
        );
        $db_waste_count = $revisions + $transients;
        $db_score = max(0, 100 - ($db_waste_count > 50 ? $weights['db_waste_penalty'] : 0));

        // 4. Performance Pillar (0-100)
        $has_page_cache = defined('WP_CACHE') && WP_CACHE;
        $has_object_cache = wp_using_ext_object_cache();
        $perf_score = 85 + ($has_page_cache ? 8 : 0) + ($has_object_cache ? 7 : 0);

        // Calculate weighted average
        $overall_score = (int)round(
            ($links_score * 0.30) + 
            ($media_score * 0.25) + 
            ($db_score * 0.25) + 
            ($perf_score * 0.20)
        );

        return [
            'score' => $overall_score,
            'weights' => $weights,
            'pillars' => [
                'performance' => [
                    'name' => __('Performance', 'on-toolkit'),
                    'score' => $perf_score,
                    'icon' => $perf_score >= 90 ? '🟢' : '🟡',
                ],
                'database' => [
                    'name' => __('Database', 'on-toolkit'),
                    'score' => $db_score,
                    'waste_items' => $db_waste_count,
                    'icon' => $db_score >= 90 ? '🟢' : '🟡',
                ],
                'links' => [
                    'name' => __('Broken Links', 'on-toolkit'),
                    'score' => $links_score,
                    'count' => $broken_count,
                    'icon' => $links_score >= 90 ? '🟢' : '🔴',
                ],
                'media' => [
                    'name' => __('Media Library', 'on-toolkit'),
                    'score' => $media_score,
                    'missing_alt' => $missing_alt,
                    'icon' => $media_score >= 90 ? '🟢' : '🟡',
                ],
            ],
        ];
    }
}

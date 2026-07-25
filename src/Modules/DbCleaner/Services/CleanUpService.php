<?php

namespace ONToolkit\Modules\DbCleaner\Services;

if (!defined('ABSPATH')) {
    exit;
}

class CleanUpService
{
    /**
     * @return array<string, mixed>
     */
    public function getAuditSummary(): array
    {
        global $wpdb;

        $revisions_count = (int)$wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        $trash_count = (int)$wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'trash'");
        $transients_count = (int)$wpdb->get_var("SELECT COUNT(option_name) FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < " . time());
        $spam_count = (int)$wpdb->get_var("SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'spam'");
        $orphan_meta_count = (int)$wpdb->get_var("SELECT COUNT(meta_id) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL");

        $est_bytes = ($revisions_count * 15000) + ($trash_count * 20000) + ($transients_count * 500) + ($spam_count * 1000) + ($orphan_meta_count * 200);

        return [
            'total_recoverable_items' => $revisions_count + $trash_count + $transients_count + $spam_count + $orphan_meta_count,
            'total_estimated_bytes' => $est_bytes,
            'total_formatted_size' => size_format($est_bytes),
            'revisions' => [
                'count' => $revisions_count,
                'formatted_size' => size_format($revisions_count * 15000),
            ],
            'trash_posts' => [
                'count' => $trash_count,
                'formatted_size' => size_format($trash_count * 20000),
            ],
            'expired_transients' => [
                'count' => $transients_count,
                'formatted_size' => size_format($transients_count * 500),
            ],
            'spam_comments' => [
                'count' => $spam_count,
                'formatted_size' => size_format($spam_count * 1000),
            ],
            'orphan_postmeta' => [
                'count' => $orphan_meta_count,
                'formatted_size' => size_format($orphan_meta_count * 200),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cleanTarget(string $target, bool $dry_run = false): array
    {
        global $wpdb;

        if ($dry_run) {
            $summary = $this->getAuditSummary();
            return [
                'dry_run' => true,
                'target' => $target,
                'summary' => $summary[$target] ?? [],
            ];
        }

        $deleted_count = 0;

        switch ($target) {
            case 'revisions':
                $deleted_count = (int)$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision' LIMIT 500");
                break;
            case 'trash_posts':
                $deleted_count = (int)$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'trash' LIMIT 500");
                break;
            case 'expired_transients':
                $expired_keys = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < " . time() . " LIMIT 500");
                foreach ($expired_keys as $timeout_key) {
                    $transient_key = str_replace('_transient_timeout_', '_transient_', $timeout_key);
                    delete_option($timeout_key);
                    delete_option($transient_key);
                    $deleted_count++;
                }
                break;
            case 'orphan_postmeta':
                $deleted_count = (int)$wpdb->query("DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL LIMIT 500");
                break;
        }

        return [
            'success' => true,
            'target' => $target,
            'cleaned_count' => $deleted_count,
        ];
    }
}

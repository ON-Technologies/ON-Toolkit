<?php

namespace ONToolkit\Modules\DbCleaner\Services;

/**
 * Service providing safe, batched database cleanup operations with dry-run support.
 */
class CleanUpService
{
    /**
     * Run a comprehensive audit of cleanable database items and estimated size recovery.
     */
    public function getAuditSummary(): array
    {
        global $wpdb;

        // 1. Revisions count & estimated size
        $revisions_count = (int)$wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'revision'"
        );
        $revisions_bytes = (int)$wpdb->get_var(
            "SELECT SUM(LENGTH(post_content) + LENGTH(post_title)) FROM {$wpdb->posts} WHERE post_type = 'revision'"
        );

        // 2. Trash posts count & estimated size
        $trash_count = (int)$wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'trash'"
        );
        $trash_bytes = (int)$wpdb->get_var(
            "SELECT SUM(LENGTH(post_content)) FROM {$wpdb->posts} WHERE post_status = 'trash'"
        );

        // 3. Expired Transients
        $time = time();
        $expired_transients_count = (int)$wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(option_name) FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 AND option_value < %d",
                '_transient_timeout_%',
                $time
            )
        );

        // 4. Spam comments
        $spam_comments_count = (int)$wpdb->get_var(
            "SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'spam' OR comment_approved = 'trash'"
        );

        // 5. Orphan Postmeta
        $orphan_postmeta_count = (int)$wpdb->get_var(
            "SELECT COUNT(meta_id) FROM {$wpdb->postmeta} pm 
             LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id 
             WHERE wp.ID IS NULL"
        );

        $total_bytes = $revisions_bytes + $trash_bytes + ($expired_transients_count * 512) + ($orphan_postmeta_count * 256);

        return [
            'revisions' => [
                'count' => $revisions_count,
                'estimated_bytes' => $revisions_bytes,
                'formatted_size' => size_format($revisions_bytes),
            ],
            'trash_posts' => [
                'count' => $trash_count,
                'estimated_bytes' => $trash_bytes,
                'formatted_size' => size_format($trash_bytes),
            ],
            'expired_transients' => [
                'count' => $expired_transients_count,
                'estimated_bytes' => $expired_transients_count * 512,
                'formatted_size' => size_format($expired_transients_count * 512),
            ],
            'spam_comments' => [
                'count' => $spam_comments_count,
                'estimated_bytes' => $spam_comments_count * 1024,
                'formatted_size' => size_format($spam_comments_count * 1024),
            ],
            'orphan_postmeta' => [
                'count' => $orphan_postmeta_count,
                'estimated_bytes' => $orphan_postmeta_count * 256,
                'formatted_size' => size_format($orphan_postmeta_count * 256),
            ],
            'total_recoverable_bytes' => $total_bytes,
            'total_formatted_size' => size_format($total_bytes),
        ];
    }

    /**
     * Clean specific database target in safe, batched chunks (`LIMIT 500`).
     */
    public function cleanTarget(string $target, bool $dry_run = false): array
    {
        if ($dry_run) {
            $summary = $this->getAuditSummary();
            return [
                'success' => true,
                'dry_run' => true,
                'target' => $target,
                'details' => $summary[$target] ?? null,
            ];
        }

        global $wpdb;
        $deleted_count = 0;
        $batch_limit = 500;

        switch ($target) {
            case 'revisions':
                do {
                    $ids = $wpdb->get_col(
                        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' LIMIT {$batch_limit}"
                    );
                    if (empty($ids)) {
                        break;
                    }
                    foreach ($ids as $id) {
                        wp_delete_post_revision($id);
                        $deleted_count++;
                    }
                } while (count($ids) === $batch_limit);
                break;

            case 'trash_posts':
                do {
                    $ids = $wpdb->get_col(
                        "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash' LIMIT {$batch_limit}"
                    );
                    if (empty($ids)) {
                        break;
                    }
                    foreach ($ids as $id) {
                        wp_delete_post($id, true);
                        $deleted_count++;
                    }
                } while (count($ids) === $batch_limit);
                break;

            case 'expired_transients':
                $time = time();
                // Delete transient timeouts and their corresponding data
                $deleted_count = (int)$wpdb->query(
                    $wpdb->prepare(
                        "DELETE a, b FROM {$wpdb->options} a 
                         INNER JOIN {$wpdb->options} b 
                         ON b.option_name = REPLACE(a.option_name, '_timeout', '') 
                         WHERE a.option_name LIKE %s 
                         AND a.option_value < %d",
                        '_transient_timeout_%',
                        $time
                    )
                );
                break;

            case 'spam_comments':
                do {
                    $comment_ids = $wpdb->get_col(
                        "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'spam' OR comment_approved = 'trash' LIMIT {$batch_limit}"
                    );
                    if (empty($comment_ids)) {
                        break;
                    }
                    foreach ($comment_ids as $cid) {
                        wp_delete_comment($cid, true);
                        $deleted_count++;
                    }
                } while (count($comment_ids) === $batch_limit);
                break;

            case 'orphan_postmeta':
                $deleted_count = (int)$wpdb->query(
                    "DELETE pm FROM {$wpdb->postmeta} pm 
                     LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id 
                     WHERE wp.ID IS NULL"
                );
                break;
        }

        return [
            'success' => true,
            'dry_run' => false,
            'target' => $target,
            'deleted_count' => $deleted_count,
        ];
    }
}

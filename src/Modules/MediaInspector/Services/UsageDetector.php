<?php

namespace ONToolkit\Modules\MediaInspector\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service to audit Media Library attachments: duplicate filenames, duplicate SHA-256 hashes, 
 * unused thumbnails, wrong dimensions, huge PNGs (>1MB), huge JPGs (>500KB), and SVG detection.
 */
class UsageDetector
{
    /**
     * Audit media library items with enhanced filter rules.
     *
     * @return array<string, mixed>
     */
    public function auditMedia(int $limit = 50, int $offset = 0, string $filter = 'all'): array
    {
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query = new \WP_Query($args);
        /** @var array<int, \WP_Post> $attachments */
        $attachments = array_filter((array)$query->posts, function ($post) {
            return $post instanceof \WP_Post;
        });
        $total_found = $query->found_posts;

        $items = [];
        $summary = [
            'total_audited' => $total_found,
            'unused_count' => 0,
            'missing_alt_count' => 0,
            'duplicate_filenames' => 0,
            'duplicate_hashes' => 0,
            'huge_png_count' => 0,
            'huge_jpg_count' => 0,
            'svg_count' => 0,
            'wrong_dimensions_count' => 0,
        ];

        /** @var array<string, int> $filenames */
        $filenames = [];
        /** @var array<string, int> $hashes */
        $hashes = [];

        foreach ($attachments as $attachment) {
            $id = $attachment->ID;
            $file_path = get_attached_file($id);
            $filename = basename($file_path ? (string)$file_path : '');
            $file_size = ($file_path && file_exists($file_path)) ? (int)filesize($file_path) : 0;
            $mime_type = (string)$attachment->post_mime_type;
            $alt_text = (string)get_post_meta($id, '_wp_attachment_image_alt', true);
            /** @var array<string, mixed> $meta */
            $meta = (array)wp_get_attachment_metadata($id);
            $width = (int)($meta['width'] ?? 0);
            $height = (int)($meta['height'] ?? 0);
            $dimensions = ($width && $height) ? "{$width}x{$height}" : 'Unknown';
            $raw_url = wp_get_attachment_url($id);
            $url = is_string($raw_url) ? $raw_url : '';

            // 1. Duplicate Filename Check
            $is_dup_filename = false;
            if (!empty($filename)) {
                if (isset($filenames[$filename])) {
                    $is_dup_filename = true;
                    $summary['duplicate_filenames']++;
                } else {
                    $filenames[$filename] = $id;
                }
            }

            // 2. Duplicate SHA-256 Content Hash Check
            $is_dup_hash = false;
            if ($file_path && file_exists($file_path) && $file_size < 10 * 1024 * 1024) { // Hash files < 10MB
                $hash = hash_file('sha256', $file_path);
                if ($hash && isset($hashes[$hash])) {
                    $is_dup_hash = true;
                    $summary['duplicate_hashes']++;
                } elseif ($hash) {
                    $hashes[$hash] = $id;
                }
            }

            // 3. Huge PNG (> 1MB) & Huge JPG (> 500KB)
            $is_huge_png = ($mime_type === 'image/png' && $file_size > 1 * 1024 * 1024);
            $is_huge_jpg = (($mime_type === 'image/jpeg' || $mime_type === 'image/jpg') && $file_size > 500 * 1024);

            if ($is_huge_png) $summary['huge_png_count']++;
            if ($is_huge_jpg) $summary['huge_jpg_count']++;

            // 4. SVG Detection
            $is_svg = ($mime_type === 'image/svg+xml' || (strlen($filename) >= 4 && strtolower(substr($filename, -4)) === '.svg'));
            if ($is_svg) $summary['svg_count']++;

            // 5. Wrong / Oversized Dimensions (> 3000px width/height)
            $is_wrong_dimensions = ($width > 3000 || $height > 3000);
            if ($is_wrong_dimensions) $summary['wrong_dimensions_count']++;

            // 6. Usage & Alt Text
            $usage = $this->getAttachmentUsage($id, $url);
            $usage_count = count($usage);
            $is_unused = ($usage_count === 0);
            $has_missing_alt = (strncmp($mime_type, 'image/', 6) === 0) && empty(trim($alt_text));

            if ($is_unused) $summary['unused_count']++;
            if ($has_missing_alt) $summary['missing_alt_count']++;

            // Filtering
            if ($filter === 'unused' && !$is_unused) continue;
            if ($filter === 'missing_alt' && !$has_missing_alt) continue;
            if ($filter === 'huge_png' && !$is_huge_png) continue;
            if ($filter === 'huge_jpg' && !$is_huge_jpg) continue;
            if ($filter === 'svg' && !$is_svg) continue;
            if ($filter === 'duplicates' && !$is_dup_filename && !$is_dup_hash) continue;

            $items[] = [
                'id' => $id,
                'title' => $attachment->post_title,
                'filename' => $filename,
                'url' => $url,
                'mime_type' => $mime_type,
                'file_size' => $file_size,
                'formatted_size' => size_format($file_size),
                'dimensions' => $dimensions,
                'upload_date' => $attachment->post_date,
                'alt_text' => $alt_text,
                'has_alt_text' => !empty(trim($alt_text)),
                'usage_count' => $usage_count,
                'is_unused' => $is_unused,
                'is_dup_filename' => $is_dup_filename,
                'is_dup_hash' => $is_dup_hash,
                'is_huge_png' => $is_huge_png,
                'is_huge_jpg' => $is_huge_jpg,
                'is_svg' => $is_svg,
                'is_wrong_dimensions' => $is_wrong_dimensions,
            ];
        }

        return [
            'items' => $items,
            'total' => $total_found,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAttachmentUsage(int $attachment_id, string $url): array
    {
        global $wpdb;
        $locations = [];

        /** @var array<int, object{post_id: int}> $featured_posts */
        $featured_posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d LIMIT 5",
                $attachment_id
            )
        );
        foreach ($featured_posts as $fp) {
            $locations[] = [
                'type' => 'featured_image',
                'post_id' => (int)$fp->post_id,
                'title' => get_the_title((int)$fp->post_id),
            ];
        }

        $custom_logo_id = (int)get_theme_mod('custom_logo');
        if ($custom_logo_id === $attachment_id) {
            $locations[] = [
                'type' => 'theme_logo',
                'post_id' => 0,
                'title' => 'Theme Custom Logo',
            ];
        }

        if (!empty($url)) {
            $filename = basename($url);
            /** @var array<int, object{ID: int, post_title: string, post_type: string}> $content_matches */
            $content_matches = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID, post_title, post_type FROM {$wpdb->posts} 
                     WHERE post_status = 'publish' 
                     AND post_content LIKE %s 
                     LIMIT 5",
                    '%' . $wpdb->esc_like($filename) . '%'
                )
            );
            foreach ($content_matches as $cm) {
                $locations[] = [
                    'type' => 'post_content',
                    'post_id' => (int)$cm->ID,
                    'post_type' => $cm->post_type,
                    'title' => $cm->post_title,
                ];
            }
        }

        return $locations;
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteUnusedAttachment(int $attachment_id, bool $force = false): array
    {
        $raw_url = wp_get_attachment_url($attachment_id);
        $url = is_string($raw_url) ? $raw_url : '';
        $usage = $this->getAttachmentUsage($attachment_id, $url);

        if (count($usage) > 0 && !$force) {
            return [
                'success' => false,
                'message' => 'Attachment is currently in use across site content and cannot be safely deleted.',
                'usage' => $usage,
            ];
        }

        $deleted = wp_delete_attachment($attachment_id, true);
        return [
            'success' => (bool)$deleted,
            'attachment_id' => $attachment_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateAltText(int $attachment_id, string $new_alt_text): array
    {
        $previous_alt = (string)get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($new_alt_text));

        return [
            'success' => true,
            'attachment_id' => $attachment_id,
            'new_alt_text' => $new_alt_text,
            'previous_alt_text' => $previous_alt,
        ];
    }
}

<?php

namespace ONToolkit\Modules\LinkScanner\Crawler;

/**
 * Extracts internal and external URLs from post content, Gutenberg blocks, and Elementor JSON.
 */
class PostCrawler
{
    /**
     * Extract unique HTTP/HTTPS URLs from post HTML & Elementor metadata.
     */
    public function extractUrlsFromPost(int $post_id): array
    {
        $urls = [];
        $post = get_post($post_id);

        if (!$post) {
            return $urls;
        }

        // 1. Extract from post_content HTML
        if (!empty($post->post_content)) {
            $urls = array_merge($urls, $this->parseHtmlUrls($post->post_content));
        }

        // 2. Extract from Elementor JSON metadata if available
        $elementor_data = get_post_meta($post_id, '_elementor_data', true);
        if (!empty($elementor_data)) {
            $urls = array_merge($urls, $this->parseElementorJson($elementor_data));
        }

        return array_unique(array_filter($urls));
    }

    /**
     * Regex extraction of href attribute values from HTML markup.
     */
    public function parseHtmlUrls(string $html): array
    {
        $urls = [];
        // Matches href="..." or href='...'
        if (preg_match_all('/<a\s+(?:[^>]*?\s+)?href=["\']([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $url) {
                $cleaned = trim($url);
                if (strncmp($cleaned, 'http://', 7) === 0 || strncmp($cleaned, 'https://', 8) === 0) {
                    $urls[] = strtok($cleaned, '#'); // strip anchor fragment
                }
            }
        }
        return $urls;
    }

    /**
     * Recursively extract URLs from Elementor JSON element structure.
     * @param string|array $elementor_data
     */
    public function parseElementorJson($elementor_data): array
    {
        $urls = [];
        $data = is_string($elementor_data) ? json_decode($elementor_data, true) : $elementor_data;

        if (!is_array($data)) {
            return $urls;
        }

        array_walk_recursive($data, function ($value, $key) use (&$urls) {
            if (is_string($value) && (strncmp($value, 'http://', 7) === 0 || strncmp($value, 'https://', 8) === 0)) {
                $urls[] = strtok(trim($value), '#');
            }
        });

        return $urls;
    }
}

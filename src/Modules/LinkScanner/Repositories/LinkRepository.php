<?php

namespace ONToolkit\Modules\LinkScanner\Repositories;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository pattern for managing ontk_links table with JSON occurrences.
 */
class LinkRepository
{
    /**
     * Save or update link status in ontk_links.
     */
    public function saveLink(array $data, array $occurrences = []): int
    {
        global $wpdb;

        $url = $data['url'];
        $url_hash = md5($url);
        $status_code = (int)($data['status_code'] ?? 0);
        $status_type = $data['status_type'] ?? 'unknown';
        $redirect_url = $data['redirect_url'] ?? null;
        $response_time_ms = (int)($data['response_time_ms'] ?? 0);
        $now = current_time('mysql');

        $table_links = "{$wpdb->prefix}ontk_links";

        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT id, occurrences FROM {$table_links} WHERE url_hash = %s", $url_hash),
            ARRAY_A
        );

        $occurrences_json = !empty($occurrences) ? wp_json_encode($occurrences) : null;

        if ($existing) {
            $wpdb->update(
                $table_links,
                [
                    'status_code' => $status_code,
                    'status_type' => $status_type,
                    'redirect_url' => $redirect_url,
                    'response_time_ms' => $response_time_ms,
                    'occurrences' => $occurrences_json,
                    'last_checked_at' => $now,
                ],
                ['id' => $existing['id']],
                ['%d', '%s', '%s', '%d', '%s', '%s'],
                ['%d']
            );
            return (int)$existing['id'];
        }

        $wpdb->insert(
            $table_links,
            [
                'url_hash' => $url_hash,
                'url' => $url,
                'status_code' => $status_code,
                'status_type' => $status_type,
                'redirect_url' => $redirect_url,
                'response_time_ms' => $response_time_ms,
                'occurrences' => $occurrences_json,
                'last_checked_at' => $now,
            ],
            ['%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s']
        );

        return (int)$wpdb->insert_id;
    }

    /**
     * Query links with pagination & filtering.
     */
    public function getLinks(string $status_type = 'all', int $limit = 50, int $offset = 0, string $search = ''): array
    {
        global $wpdb;
        $table_links = "{$wpdb->prefix}ontk_links";

        $where = ["1=1"];
        $params = [];

        if ($status_type !== 'all') {
            $where[] = "status_type = %s";
            $params[] = $status_type;
        }

        if (!empty($search)) {
            $where[] = "url LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $where_sql = implode(' AND ', $where);

        $total_sql = "SELECT COUNT(id) FROM {$table_links} WHERE {$where_sql}";
        $query_total = !empty($params) ? $wpdb->prepare($total_sql, $params) : $total_sql;
        $total = (int)$wpdb->get_var($query_total);

        $sql = "SELECT * FROM {$table_links} WHERE {$where_sql} ORDER BY last_checked_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        foreach ($results as &$row) {
            $row['occurrences'] = !empty($row['occurrences']) ? json_decode($row['occurrences'], true) : [];
        }

        return [
            'items' => $results,
            'total' => $total,
        ];
    }
}

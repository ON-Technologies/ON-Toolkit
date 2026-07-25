<?php

namespace ONToolkit\Core\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles custom database table schema (Single ontk_links table with JSON occurrences).
 */
class Schema {

	public const DB_VERSION = '1.1.0';

	public static function activate(): void {
		self::createTables();
		update_option( 'ontk_db_version', self::DB_VERSION );
	}

	public static function deactivate(): void {
	}

	public static function createTables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_links     = "{$wpdb->prefix}ontk_links";

		$sql = "CREATE TABLE {$table_links} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            url_hash char(32) NOT NULL,
            url text NOT NULL,
            status_code smallint(5) UNSIGNED DEFAULT 0,
            status_type varchar(20) DEFAULT 'unknown',
            redirect_url text DEFAULT NULL,
            response_time_ms int(10) UNSIGNED DEFAULT 0,
            occurrences json DEFAULT NULL,
            last_checked_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY url_hash (url_hash),
            KEY status_type (status_type),
            KEY status_code (status_code)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Drop legacy occurrences table if exists
		$table_old = "{$wpdb->prefix}ontk_link_occurrences";
		$wpdb->query( "DROP TABLE IF EXISTS {$table_old}" );
	}
}

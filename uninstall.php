<?php
/**
 * ON Toolkit Uninstall
 *
 * Runs when the plugin is uninstalled via WordPress admin.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean options if explicitly enabled in settings or by default
delete_option('ontk_settings');
delete_option('ontk_active_modules');
delete_option('ontk_db_version');

global $wpdb;

// Drop custom plugin tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ontk_link_occurrences");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ontk_links");

// Clear transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ontk_%' OR option_name LIKE '_transient_timeout_ontk_%'");

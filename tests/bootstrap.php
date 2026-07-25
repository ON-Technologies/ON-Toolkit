<?php

define('ABSPATH', sys_get_temp_dir() . '/');
define('ONTK_VERSION', '1.0.0');
define('ONTK_PLUGIN_DIR', dirname(__DIR__) . '/');
define('ONTK_PLUGIN_URL', 'http://example.com/wp-content/plugins/on-toolkit/');

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Mock minimal WordPress functions if WP environment is not loaded
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {}
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return $text; }
}
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}
if (!function_exists('wp_using_ext_object_cache')) {
    function wp_using_ext_object_cache($using = null) { return false; }
}

// Mock $wpdb global
$GLOBALS['wpdb'] = new class {
    public $prefix = 'wp_';
    public $posts = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    public $options = 'wp_options';
    
    public function get_var($query) {
        return 0; // Return 0 for count queries
    }
    
    public function prepare($query, ...$args) {
        return $query;
    }
};

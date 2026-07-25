<?php
/**
 * Plugin Name: ON Toolkit
 * Plugin URI:  https://ontoolkit.com
 * Description: The ultra-high-performance, modular WordPress administration toolkit. Clean, fast, and simple.
 * Version:     1.0.0
 * Author:      ON Toolkit Team
 * Author URI:  https://ontoolkit.com
 * Donate link: https://github.com/sponsors/Tksharmely
 * Text Domain: on-toolkit
 * Domain Path: /languages
 * License:     GPL-2.0-or-later
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONTK_VERSION', '1.0.0' );
define( 'ONTK_PLUGIN_FILE', __FILE__ );
define( 'ONTK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ONTK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload Composer dependencies
if ( file_exists( ONTK_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once ONTK_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	// Basic fallback PSR-4 autoloader when vendor is not generated
	spl_autoload_register(
		function ( $class_name ) {
			$prefix   = 'ONToolkit\\';
			$base_dir = ONTK_PLUGIN_DIR . 'src/';
			$len      = strlen( $prefix );

			if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class_name, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

// Ensure PHP 7.4+ compatibility
if ( version_compare( PHP_VERSION, '7.4.0', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' .
			esc_html__( 'ON Toolkit requires PHP version 7.4 or higher.', 'on-toolkit' ) .
			'</p></div>';
		}
	);
	return;
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, array( 'ONToolkit\\Core\\Database\\Schema', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ONToolkit\\Core\\Database\\Schema', 'deactivate' ) );

// Boot the plugin container
add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'on-toolkit', false, dirname( plugin_basename( ONTK_PLUGIN_FILE ) ) . '/languages' );
		\ONToolkit\Plugin::getInstance()->boot();
	}
);

// Load WP-CLI commands if running via CLI
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once ONTK_PLUGIN_DIR . 'bin/wp-cli.php';
}

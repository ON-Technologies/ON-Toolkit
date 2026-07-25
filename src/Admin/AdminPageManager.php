<?php

namespace ONToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ONToolkit\Core\ModuleRegistry;

/**
 * Registers WordPress Native Admin menu, page views, and asset enqueuing.
 */
class AdminPageManager {

	private ModuleRegistry $moduleRegistry;

	public function __construct( ModuleRegistry $moduleRegistry ) {
		$this->moduleRegistry = $moduleRegistry;
	}

	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'registerAdminMenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	public function registerAdminMenu(): void {
		add_menu_page(
			__( 'ON Toolkit', 'on-toolkit' ),
			__( 'ON Toolkit', 'on-toolkit' ),
			'manage_options',
			'on-toolkit',
			array( $this, 'renderDashboardView' ),
			'dashicons-superhero',
			30
		);
	}

	public function enqueueAssets( string $hook_suffix ): void {
		// Enqueue assets ONLY on ON Toolkit admin page
		if ( $hook_suffix !== 'toplevel_page_on-toolkit' ) {
			return;
		}

		wp_enqueue_style( 'ontk-admin-app-css', ONTK_PLUGIN_URL . 'assets/css/admin-app.css', array(), ONTK_VERSION );
		wp_enqueue_script( 'ontk-admin-app-js', ONTK_PLUGIN_URL . 'assets/js/admin-app.js', array(), ONTK_VERSION, true );

		wp_localize_script(
			'ontk-admin-app-js',
			'ontkAppConfig',
			array(
				'apiUrl'  => esc_url_raw( rest_url( 'on-toolkit/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'modules' => array_keys( $this->moduleRegistry->getModules() ),
			)
		);
	}

	public function renderDashboardView(): void {
		?>
		<div id="ontk-app-root" class="wrap ontk-wrap">
			<div class="ontk-loading-state">
				<span class="spinner is-active" style="float:none; margin:0 8px 0 0;"></span>
				<?php esc_html_e( 'Initializing ON Toolkit Dashboard...', 'on-toolkit' ); ?>
			</div>
		</div>
		<?php
	}
}

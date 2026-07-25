<?php

namespace ONToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ONToolkit\Core\ModuleRegistry;
use ONToolkit\Modules\LinkScanner\LinkScannerModule;
use ONToolkit\Modules\MediaInspector\MediaInspectorModule;
use ONToolkit\Modules\DbCleaner\DbCleanerModule;
use ONToolkit\Admin\AdminPageManager;

/**
 * Main ON Toolkit Plugin Bootstrapper Singleton.
 */
class Plugin {

	private static ?Plugin $instance = null;
	private ModuleRegistry $moduleRegistry;

	private function __construct() {
		$this->moduleRegistry = new ModuleRegistry();
	}

	public static function getInstance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Boot all plugin modules, REST controllers, and admin UI.
	 */
	public function boot(): void {
		// 1. Register core modules
		$this->moduleRegistry->register( new LinkScannerModule() );
		$this->moduleRegistry->register( new MediaInspectorModule() );
		$this->moduleRegistry->register( new DbCleanerModule() );

		// 2. Boot active modules (zero overhead for disabled modules)
		$this->moduleRegistry->bootActiveModules();

		// 3. Register Core REST Controllers
		add_action(
			'rest_api_init',
			function () {
				$healthController = new \ONToolkit\Core\Rest\HealthScoreController();
				$healthController->register_routes();
			}
		);

		// 4. Register Native Admin Page Manager
		if ( is_admin() ) {
			$adminManager = new AdminPageManager( $this->moduleRegistry );
			$adminManager->boot();
		}
	}

	public function getModuleRegistry(): ModuleRegistry {
		return $this->moduleRegistry;
	}
}

<?php

namespace ONToolkit\Modules\DbCleaner;

use ONToolkit\Core\AbstractModule;
use ONToolkit\Modules\DbCleaner\Services\CleanUpService;
use ONToolkit\Modules\DbCleaner\Rest\DbCleanerController;

class DbCleanerModule extends AbstractModule {

	private CleanUpService $cleanUpService;

	public function __construct() {
		$this->cleanUpService = new CleanUpService();
	}

	public function getId(): string {
		return 'db_cleaner';
	}

	public function getName(): string {
		return __( 'Database Cleanup', 'on-toolkit' );
	}

	public function getDescription(): string {
		return __( 'Safely clean unnecessary WordPress revisions, trash, expired transients, and orphan metadata.', 'on-toolkit' );
	}

	public function boot(): void {
		// Registers REST endpoints and hooks
		add_action(
			'rest_api_init',
			function () {
				$controller = new DbCleanerController( $this->cleanUpService );
				$controller->register_routes();
			}
		);
	}

	public function getRestControllers(): array {
		return array(
			new DbCleanerController( $this->cleanUpService ),
		);
	}

	public function getService(): CleanUpService {
		return $this->cleanUpService;
	}
}

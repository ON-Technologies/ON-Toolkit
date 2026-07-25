<?php

namespace ONToolkit\Modules\MediaInspector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ONToolkit\Core\AbstractModule;
use ONToolkit\Core\Rest\RestController;
use ONToolkit\Modules\MediaInspector\Services\UsageDetector;
use ONToolkit\Modules\MediaInspector\Rest\MediaInspectorController;

class MediaInspectorModule extends AbstractModule {

	public function getId(): string {
		return 'media_inspector';
	}

	public function getName(): string {
		return __( 'Media Inspector', 'on-toolkit' );
	}

	public function getDescription(): string {
		return __( 'Audit unused attachments, duplicate filenames, duplicate SHA-256 hashes, missing ALT text, huge PNGs (>1MB), huge JPGs (>500KB), and SVGs.', 'on-toolkit' );
	}

	public function boot(): void {
		$usageDetector = new UsageDetector();

		add_action(
			'rest_api_init',
			function () use ( $usageDetector ) {
				$controller = new MediaInspectorController( $usageDetector );
				$controller->register_routes();
			}
		);
	}

	/**
	 * @return array<int, RestController>
	 */
	public function getRestControllers(): array {
		$usageDetector = new UsageDetector();

		return array(
			new MediaInspectorController( $usageDetector ),
		);
	}
}

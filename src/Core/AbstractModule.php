<?php

namespace ONToolkit\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ONToolkit\Core\Rest\RestController;

abstract class AbstractModule implements ModuleInterface {

	protected bool $enabled = true;

	public function isEnabled(): bool {
		return $this->enabled;
	}

	/**
	 * @return array<int, RestController>
	 */
	public function getRestControllers(): array {
		return array();
	}
}

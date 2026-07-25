<?php

namespace ONToolkit\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ONToolkit\Core\Rest\RestController;

interface ModuleInterface {

	public function getId(): string;
	public function getName(): string;
	public function getDescription(): string;
	public function isEnabled(): bool;
	public function boot(): void;

	/**
	 * @return array<int, RestController>
	 */
	public function getRestControllers(): array;
}

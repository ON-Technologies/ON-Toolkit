<?php

namespace ONToolkit\Core\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Request;

abstract class RestController extends WP_REST_Controller {

	/**
	 * @var string
	 */
	protected $namespace = 'on-toolkit/v1';

	/**
	 * @return non-falsy-string
	 */
	public function getNamespace(): string {
		return 'on-toolkit/v1';
	}

	public function checkPermission( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @param array<string, mixed>|object|null $data
	 */
	protected function respondSuccess( $data = null, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			$status
		);
	}

	protected function respondError( string $message, string $code = 'ontk_error', int $status = 400 ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'success' => false,
				'code'    => $code,
				'message' => $message,
			),
			$status
		);
	}
}

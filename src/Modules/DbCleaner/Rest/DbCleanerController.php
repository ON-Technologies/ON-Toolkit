<?php

namespace ONToolkit\Modules\DbCleaner\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ONToolkit\Core\Rest\RestController;
use ONToolkit\Modules\DbCleaner\Services\CleanUpService;
use WP_REST_Request;
use WP_REST_Response;

class DbCleanerController extends RestController {

	private CleanUpService $cleanUpService;

	public function __construct( CleanUpService $cleanUpService ) {
		$this->cleanUpService = $cleanUpService;
	}

	public function register_routes(): void {
		$namespace = $this->getNamespace();

		register_rest_route(
			$namespace,
			'/db-cleaner/audit',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'getAuditSummary' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/db-cleaner/clean',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'cleanTarget' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function getAuditSummary( WP_REST_Request $request ): WP_REST_Response {
		$summary = $this->cleanUpService->getAuditSummary();
		return $this->respondSuccess( $summary );
	}

	public function cleanTarget( WP_REST_Request $request ): WP_REST_Response {
		$target  = sanitize_text_field( (string) ( $request->get_param( 'target' ) ?? '' ) );
		$dry_run = (bool) $request->get_param( 'dry_run' );
		$confirm = sanitize_text_field( (string) ( $request->get_param( 'confirm_action' ) ?? '' ) );

		if ( ! $dry_run && $confirm !== 'CONFIRM_CLEANUP' ) {
			return $this->respondError( 'Action requires explicit confirmation string CONFIRM_CLEANUP', 'ontk_unconfirmed', 400 );
		}

		$result = $this->cleanUpService->cleanTarget( $target, $dry_run );
		return $this->respondSuccess( $result );
	}
}

<?php

namespace ONToolkit\Core\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ONToolkit\Core\Services\HealthScoreCalculator;
use WP_REST_Request;
use WP_REST_Response;

class HealthScoreController extends RestController {

	private HealthScoreCalculator $calculator;

	public function __construct() {
		$this->calculator = new HealthScoreCalculator();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->getNamespace(),
			'/health-score',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'getHealthScore' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function getHealthScore( WP_REST_Request $request ): WP_REST_Response {
		$score_data = $this->calculator->calculateScore();
		return $this->respondSuccess( $score_data );
	}
}

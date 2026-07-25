<?php

namespace ONToolkit\Core\Rest;

if (!defined('ABSPATH')) {
    exit;
}

use ONToolkit\Core\Services\HealthScoreCalculator;
use WP_REST_Request;
use WP_REST_Response;

class HealthScoreController extends RestController
{
    private HealthScoreCalculator $calculator;

    public function __construct()
    {
        $this->calculator = new HealthScoreCalculator();
    }

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/health-score', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getHealthScore'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);
    }

    public function getHealthScore(WP_REST_Request $request): WP_REST_Response
    {
        $result = $this->calculator->calculateScore();
        return $this->respondSuccess($result);
    }
}

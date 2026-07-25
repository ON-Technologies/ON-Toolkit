<?php

namespace ONToolkit\Core\Rest;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Base REST Controller enforcing security policies across all ON Toolkit endpoints.
 */
abstract class RestController extends WP_REST_Controller
{
    protected string $namespace = 'on-toolkit/v1';

    /**
     * Permission callback: requires administrator capability (`manage_options`).
     * @return bool|WP_Error
     */
    public function checkPermission(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'ontk_rest_forbidden',
                __('You do not have sufficient permissions to access this endpoint.', 'on-toolkit'),
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * Send structured success response.
     * @param mixed $data
     */
    protected function respondSuccess($data = [], int $status = 200): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * Send structured error response.
     */
    protected function respondError(string $message, string $code = 'ontk_error', int $status = 400): WP_Error
    {
        return new WP_Error($code, $message, ['status' => $status]);
    }
}

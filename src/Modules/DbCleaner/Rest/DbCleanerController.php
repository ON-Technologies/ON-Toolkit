<?php

namespace ONToolkit\Modules\DbCleaner\Rest;

use ONToolkit\Core\Rest\RestController;
use ONToolkit\Modules\DbCleaner\Services\CleanUpService;
use WP_REST_Request;
use WP_REST_Response;

class DbCleanerController extends RestController
{
    private CleanUpService $cleanUpService;

    public function __construct(CleanUpService $cleanUpService)
    {
        $this->cleanUpService = $cleanUpService;
    }

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/db-cleaner/audit', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getAudit'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);

        register_rest_route($this->namespace, '/db-cleaner/clean', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'cleanTarget'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);
    }

    public function getAudit(WP_REST_Request $request): WP_REST_Response
    {
        $audit = $this->cleanUpService->getAuditSummary();
        return $this->respondSuccess($audit);
    }

    public function cleanTarget(WP_REST_Request $request): WP_REST_Response
    {
        $target = sanitize_text_field($request->get_param('target') ?? '');
        $dry_run = (bool)$request->get_param('dry_run');
        $confirm = sanitize_text_field($request->get_param('confirm_action') ?? '');

        if (!$dry_run && $confirm !== 'CONFIRM_CLEANUP') {
            return $this->respondError('Explicit confirmation action string required for destructive cleanup.', 'ontk_missing_confirmation', 400);
        }

        $result = $this->cleanUpService->cleanTarget($target, $dry_run);
        return $this->respondSuccess($result);
    }
}

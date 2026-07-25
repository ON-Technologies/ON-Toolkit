<?php

namespace ONToolkit\Modules\MediaInspector\Rest;

use ONToolkit\Core\Rest\RestController;
use ONToolkit\Modules\MediaInspector\Services\UsageDetector;
use WP_REST_Request;
use WP_REST_Response;

class MediaInspectorController extends RestController
{
    private UsageDetector $usageDetector;

    public function __construct(UsageDetector $usageDetector)
    {
        $this->usageDetector = $usageDetector;
    }

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/media-inspector/audit', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'auditMedia'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);

        register_rest_route($this->namespace, '/media-inspector/delete', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'deleteAttachment'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);

        register_rest_route($this->namespace, '/media-inspector/update-alt', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'updateAltText'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);

        register_rest_route($this->namespace, '/media-inspector/batch-delete', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'batchDeleteMedia'],
                'permission_callback' => [$this, 'checkPermission'],
            ],
        ]);
    }

    public function batchDeleteMedia(WP_REST_Request $request): WP_REST_Response
    {
        $ids = array_map('absint', (array)($request->get_param('ids') ?? []));
        $deleted_count = 0;

        foreach ($ids as $id) {
            $res = $this->usageDetector->deleteUnusedAttachment($id, false);
            if ($res['success']) {
                $deleted_count++;
            }
        }

        return $this->respondSuccess([
            'deleted_count' => $deleted_count,
            'total_requested' => count($ids),
        ]);
    }

    public function auditMedia(WP_REST_Request $request): WP_REST_Response
    {
        $limit = (int)($request->get_param('limit') ?? 50);
        $offset = (int)($request->get_param('offset') ?? 0);
        $filter = sanitize_text_field($request->get_param('filter') ?? 'all');

        $result = $this->usageDetector->auditMedia($limit, $offset, $filter);
        return $this->respondSuccess($result);
    }

    public function deleteAttachment(WP_REST_Request $request): WP_REST_Response
    {
        $attachment_id = (int)$request->get_param('attachment_id');
        $force = (bool)$request->get_param('force');

        if (!$attachment_id) {
            return $this->respondError('Missing attachment_id', 'ontk_missing_id', 400);
        }

        $result = $this->usageDetector->deleteUnusedAttachment($attachment_id, $force);
        if (!$result['success']) {
            return $this->respondError($result['message'] ?? 'Failed to delete attachment', 'ontk_delete_failed', 400);
        }

        return $this->respondSuccess($result);
    }

    public function updateAltText(WP_REST_Request $request): WP_REST_Response
    {
        $attachment_id = (int)$request->get_param('attachment_id');
        $alt_text = sanitize_text_field($request->get_param('alt_text') ?? '');

        if (!$attachment_id) {
            return $this->respondError('Missing attachment_id', 'ontk_missing_id', 400);
        }

        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        return $this->respondSuccess([
            'attachment_id' => $attachment_id,
            'alt_text' => $alt_text,
        ]);
    }
}

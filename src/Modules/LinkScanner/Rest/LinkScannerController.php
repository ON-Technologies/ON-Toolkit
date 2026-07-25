<?php

namespace ONToolkit\Modules\LinkScanner\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ONToolkit\Core\Rest\RestController;
use ONToolkit\Modules\LinkScanner\Crawler\PostCrawler;
use ONToolkit\Modules\LinkScanner\Services\HttpVerifier;
use ONToolkit\Modules\LinkScanner\Repositories\LinkRepository;
use ONToolkit\Modules\LinkScanner\Services\BackgroundScanner;
use WP_REST_Request;
use WP_REST_Response;

class LinkScannerController extends RestController {

	private PostCrawler $postCrawler;
	private HttpVerifier $httpVerifier;
	private LinkRepository $linkRepository;
	private BackgroundScanner $backgroundScanner;

	public function __construct(
		LinkRepository $linkRepository,
		BackgroundScanner $backgroundScanner
	) {
		$this->postCrawler       = new PostCrawler();
		$this->httpVerifier      = new HttpVerifier();
		$this->linkRepository    = $linkRepository;
		$this->backgroundScanner = $backgroundScanner;
	}

	public function register_routes(): void {
		$namespace = $this->getNamespace();

		register_rest_route(
			$namespace,
			'/link-scanner/links',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'getLinks' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/link-scanner/scan-post',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'scanPost' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/link-scanner/start-scan',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'startFullScan' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/link-scanner/scan-status',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'getScanStatus' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/link-scanner/batch-fix',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'batchFixLinks' ),
					'permission_callback' => array( $this, 'checkPermission' ),
				),
			)
		);
	}

	public function batchFixLinks( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$action = sanitize_text_field( (string) ( $request->get_param( 'action' ) ?? 'ignore' ) );
		$ids    = array_map( 'absint', (array) ( $request->get_param( 'ids' ) ?? array() ) );

		if ( empty( $ids ) ) {
			return $this->respondError( 'No link IDs provided', 'ontk_missing_ids', 400 );
		}

		$table_links  = "{$wpdb->prefix}ontk_links";
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		if ( $action === 'delete' ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table_links} WHERE id IN ({$placeholders})", $ids ) );
		} elseif ( $action === 'ignore' ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_links} SET status_type = 'ok' WHERE id IN ({$placeholders})", $ids ) );
		}

		return $this->respondSuccess(
			array(
				'action'          => $action,
				'processed_count' => count( $ids ),
			)
		);
	}

	public function startFullScan( WP_REST_Request $request ): WP_REST_Response {
		$this->backgroundScanner->dispatchScan( 0 );
		return $this->respondSuccess(
			array(
				'status'  => 'started',
				'message' => 'Background scan dispatched successfully.',
			)
		);
	}

	public function getScanStatus( WP_REST_Request $request ): WP_REST_Response {
		/** @var array<string, mixed> $status */
		$status = (array) get_option(
			'ontk_scan_status',
			array(
				'status'              => 'idle',
				'scanned_posts'       => 0,
				'total_posts'         => 0,
				'progress_percentage' => 0,
			)
		);

		$total                         = (int) ( $status['total_posts'] ?? 0 );
		$scanned                       = (int) ( $status['scanned_posts'] ?? 0 );
		$status['progress_percentage'] = $total > 0 ? (int) round( ( $scanned / $total ) * 100 ) : 0;

		return $this->respondSuccess( $status );
	}

	public function getLinks( WP_REST_Request $request ): WP_REST_Response {
		$status_type = sanitize_text_field( (string) ( $request->get_param( 'status_type' ) ?? 'all' ) );
		$limit       = (int) ( $request->get_param( 'limit' ) ?? 50 );
		$offset      = (int) ( $request->get_param( 'offset' ) ?? 0 );
		$search      = sanitize_text_field( (string) ( $request->get_param( 'search' ) ?? '' ) );

		$result = $this->linkRepository->getLinks( $status_type, $limit, $offset, $search );
		return $this->respondSuccess( $result );
	}

	public function scanPost( WP_REST_Request $request ): WP_REST_Response {
		$post_id = (int) $request->get_param( 'post_id' );

		if ( ! $post_id ) {
			return $this->respondError( 'Missing post_id', 'ontk_missing_id', 400 );
		}

		$urls            = $this->postCrawler->extractUrlsFromPost( $post_id );
		$scanned_results = array();

		foreach ( $urls as $url => $occurrences ) {
			$verification = $this->httpVerifier->checkUrl( (string) $url );
			$this->linkRepository->saveLink( $verification, $occurrences );
			$scanned_results[] = $verification;
		}

		return $this->respondSuccess(
			array(
				'post_id'    => $post_id,
				'urls_found' => count( $urls ),
				'scanned'    => $scanned_results,
			)
		);
	}
}

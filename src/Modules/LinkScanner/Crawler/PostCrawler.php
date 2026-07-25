<?php

namespace ONToolkit\Modules\LinkScanner\Crawler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PostCrawler {

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function extractUrlsFromPost( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return array();
		}

		$links = array();

		// 1. Crawl standard post_content HTML
		$html_urls = $this->parseHtmlUrls( $post->post_content, $post_id );
		foreach ( $html_urls as $url => $occurrences ) {
			$links[ $url ] = array_merge( $links[ $url ] ?? array(), $occurrences );
		}

		// 2. Crawl Elementor JSON meta if present
		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		if ( ! empty( $elementor_data ) && is_string( $elementor_data ) ) {
			$json_data = json_decode( $elementor_data, true );
			if ( is_array( $json_data ) ) {
				$elementor_urls = $this->parseElementorJson( $json_data, $post_id );
				foreach ( $elementor_urls as $url => $occurrences ) {
					$links[ $url ] = array_merge( $links[ $url ] ?? array(), $occurrences );
				}
			}
		}

		return $links;
	}

	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function parseHtmlUrls( string $content, int $post_id ): array {
		$urls = array();
		if ( empty( $content ) ) {
			return $urls;
		}

		preg_match_all( '/<a\s+(?:[^>]*?\s+)?href=["\'](https?:\/\/[^"\']+)["\']/i', $content, $matches );

		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $url ) {
				$urls[ $url ][] = array(
					'type'  => 'post_content',
					'id'    => $post_id,
					'title' => get_the_title( $post_id ),
				);
			}
		}

		return $urls;
	}

	/**
	 * @param array<mixed> $elementor_data
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function parseElementorJson( array $elementor_data, int $post_id ): array {
		$urls = array();

		array_walk_recursive(
			$elementor_data,
			function ( $value, $key ) use ( &$urls, $post_id ) {
				if ( $key === 'url' && is_string( $value ) && strncmp( $value, 'http', 4 ) === 0 ) {
					$urls[ $value ][] = array(
						'type'  => 'elementor_widget',
						'id'    => $post_id,
						'title' => get_the_title( $post_id ),
					);
				}
			}
		);

		return $urls;
	}
}

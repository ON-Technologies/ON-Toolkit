<?php

namespace ONToolkit\Modules\LinkScanner\Crawler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MenuCrawler {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function extractMenuUrls(): array {
		$menus = wp_get_nav_menus();
		$urls  = array();

		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				if ( $item instanceof \WP_Post ) {
					$url = get_post_meta( $item->ID, '_menu_item_url', true );
					if ( ! empty( $url ) && is_string( $url ) && strncmp( $url, 'http', 4 ) === 0 ) {
						$urls[] = array(
							'url'        => $url,
							'occurrence' => array(
								'type'  => 'nav_menu',
								'id'    => (int) $menu->term_id,
								'title' => $menu->name . ' -> ' . $item->post_title,
							),
						);
					}
				}
			}
		}

		return $urls;
	}
}

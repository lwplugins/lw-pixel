<?php
/**
 * WooCommerce ViewCategory event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce\Events;

use LightweightPlugins\Pixel\Events\AbstractEvent;

/**
 * Fires on a WooCommerce product category archive page.
 */
final class ViewCategory extends AbstractEvent {

	public function __construct() {
		if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return;
		}

		$term = get_queried_object();

		if ( $term instanceof \WP_Term ) {
			$this->params = [
				'content_category' => $term->name,
				'category_id'      => (string) $term->term_id,
			];
		}
	}

	public function get_name(): string {
		return 'ViewCategory';
	}

	public function should_fire(): bool {
		if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return false;
		}

		return parent::should_fire();
	}

	protected function option_key(): string {
		return 'woo_view_category';
	}
}

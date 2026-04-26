<?php
/**
 * WooCommerce ViewProduct event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce\Events;

use LightweightPlugins\Pixel\Events\AbstractEvent;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Fires on a single WooCommerce product page.
 */
final class ViewProduct extends AbstractEvent {

	public function __construct() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$wc_product = wc_get_product( get_queried_object_id() );

		if ( $wc_product instanceof \WC_Product ) {
			$this->params = ProductData::for_product( $wc_product );
		}
	}

	public function get_name(): string {
		return 'ViewContent';
	}

	public function should_fire(): bool {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return false;
		}

		return parent::should_fire() && [] !== $this->params;
	}

	protected function option_key(): string {
		return 'woo_view_product';
	}
}

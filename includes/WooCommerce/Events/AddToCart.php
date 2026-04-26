<?php
/**
 * WooCommerce AddToCart event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce\Events;

use LightweightPlugins\Pixel\Events\AbstractEvent;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Fires whenever a product is added to the cart.
 */
final class AddToCart extends AbstractEvent {

	/**
	 * Constructor.
	 *
	 * @param int $product_id Product or variation id.
	 * @param int $quantity   Quantity added.
	 */
	public function __construct( int $product_id = 0, int $quantity = 1 ) {
		if ( $product_id <= 0 ) {
			return;
		}

		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

		if ( $product instanceof \WC_Product ) {
			$this->params = ProductData::for_product( $product, $quantity );
		}
	}

	public function get_name(): string {
		return 'AddToCart';
	}

	protected function option_key(): string {
		return 'woo_add_to_cart';
	}
}

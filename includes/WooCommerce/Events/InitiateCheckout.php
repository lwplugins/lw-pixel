<?php
/**
 * WooCommerce InitiateCheckout event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce\Events;

use LightweightPlugins\Pixel\Events\AbstractEvent;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Fires on the checkout page (excluding the order-received view).
 */
final class InitiateCheckout extends AbstractEvent {

	public function __construct() {
		if ( ! self::is_checkout_view() ) {
			return;
		}

		$this->params = ProductData::for_cart();
	}

	public function get_name(): string {
		return 'InitiateCheckout';
	}

	public function should_fire(): bool {
		if ( ! self::is_checkout_view() ) {
			return false;
		}

		return parent::should_fire() && [] !== $this->params;
	}

	protected function option_key(): string {
		return 'woo_initiate_checkout';
	}

	/**
	 * Whether the current request is the checkout page (not order-received).
	 *
	 * @return bool
	 */
	private static function is_checkout_view(): bool {
		return function_exists( 'is_checkout' )
			&& is_checkout()
			&& ! ( function_exists( 'is_order_received_page' ) && is_order_received_page() );
	}
}

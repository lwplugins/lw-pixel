<?php
/**
 * WooCommerce AddPaymentInfo event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce\Events;

use LightweightPlugins\Pixel\Events\AbstractEvent;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Fires when payment info is added — on the JS side, the script tracks the
 * `update_checkout` event. Here we just expose the cart payload.
 */
final class AddPaymentInfo extends AbstractEvent {

	public function __construct() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		$this->params = ProductData::for_cart();
	}

	public function get_name(): string {
		return 'AddPaymentInfo';
	}

	public function should_fire(): bool {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return false;
		}

		return parent::should_fire() && [] !== $this->params;
	}

	protected function option_key(): string {
		return 'woo_add_payment_info';
	}
}

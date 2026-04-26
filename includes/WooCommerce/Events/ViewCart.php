<?php
/**
 * WooCommerce ViewCart event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce\Events;

use LightweightPlugins\Pixel\Events\AbstractEvent;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Fires on the WooCommerce cart page.
 */
final class ViewCart extends AbstractEvent {

	public function __construct() {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return;
		}

		$this->params = ProductData::for_cart();
	}

	public function get_name(): string {
		return 'ViewCart';
	}

	public function should_fire(): bool {
		if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
			return false;
		}

		return parent::should_fire() && [] !== $this->params;
	}

	protected function option_key(): string {
		return 'woo_view_cart';
	}
}

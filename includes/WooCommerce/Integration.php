<?php
/**
 * WooCommerce integration coordinator.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce;

use LightweightPlugins\Pixel\Events\EventManager;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\WooCommerce\Events\AddPaymentInfo;
use LightweightPlugins\Pixel\WooCommerce\Events\AddToCart;
use LightweightPlugins\Pixel\WooCommerce\Events\InitiateCheckout;
use LightweightPlugins\Pixel\WooCommerce\Events\Purchase;
use LightweightPlugins\Pixel\WooCommerce\Events\ViewCart;
use LightweightPlugins\Pixel\WooCommerce\Events\ViewCategory;
use LightweightPlugins\Pixel\WooCommerce\Events\ViewProduct;

/**
 * Wires WooCommerce hooks to the event manager.
 */
final class Integration {

	/**
	 * Event manager.
	 *
	 * @var EventManager
	 */
	private EventManager $event_manager;

	/**
	 * Orders whose Purchase is already queued in this request.
	 *
	 * @var array<int, bool>
	 */
	private array $queued_orders = [];

	/**
	 * Constructor.
	 *
	 * @param EventManager $event_manager Event manager instance.
	 */
	public function __construct( EventManager $event_manager ) {
		$this->event_manager = $event_manager;

		add_action( 'wp', [ $this, 'queue_view_events' ] );
		add_action( 'woocommerce_add_to_cart', [ $this, 'queue_add_to_cart' ], 10, 4 );
		add_action( 'woocommerce_thankyou', [ $this, 'queue_purchase' ] );
	}

	/**
	 * Queue ViewProduct, ViewCategory, InitiateCheckout, AddPaymentInfo for the current request.
	 *
	 * @return void
	 */
	public function queue_view_events(): void {
		if ( is_admin() ) {
			return;
		}

		$events = [
			new ViewProduct(),
			new ViewCategory(),
			new ViewCart(),
			new InitiateCheckout(),
			new AddPaymentInfo(),
		];

		foreach ( $events as $event ) {
			if ( $event->should_fire() ) {
				$this->event_manager->queue( $event->get_name(), $event->get_params() );
			}
		}
	}

	/**
	 * Queue an AddToCart event when the cart is mutated server-side.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id    Product id.
	 * @param int    $quantity      Quantity.
	 * @param int    $variation_id  Variation id.
	 * @return void
	 */
	public function queue_add_to_cart( string $cart_item_key, int $product_id, int $quantity, int $variation_id ): void {
		unset( $cart_item_key );

		if ( ! Options::get( 'woo_add_to_cart' ) ) {
			return;
		}

		$event = new AddToCart( $variation_id > 0 ? $variation_id : $product_id, $quantity );

		if ( $event->should_fire() ) {
			$this->event_manager->queue( $event->get_name(), $event->get_params() );
		}
	}

	/**
	 * Queue a Purchase event on the WooCommerce thank-you page.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public function queue_purchase( int $order_id ): void {
		if ( isset( $this->queued_orders[ $order_id ] ) ) {
			return;
		}

		$event = new Purchase( $order_id );

		if ( $event->should_fire() ) {
			$this->queued_orders[ $order_id ] = true;
			$this->event_manager->queue( $event->get_name(), $event->get_params(), [ $event, 'mark_tracked' ] );
		}
	}
}

<?php
/**
 * WooCommerce Purchase event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce\Events;

use LightweightPlugins\Pixel\Events\AbstractEvent;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Fires once on the order-received page.
 */
final class Purchase extends AbstractEvent {

	public const TRACKED_META = '_lw_pixel_purchase_tracked';

	/**
	 * Order statuses that are not a purchase. WooCommerce shows the
	 * thank-you page (and fires `woocommerce_thankyou`) for a declined
	 * payment too. Pending and on-hold still count: offsite gateways return
	 * the customer before their callback, and BACS / cheque orders wait
	 * on-hold for the transfer.
	 *
	 * @var array<int, string>
	 */
	public const NOT_A_PURCHASE = [ 'failed', 'cancelled' ];

	/**
	 * Order id.
	 *
	 * @var int
	 */
	private int $order_id;

	/**
	 * Constructor.
	 *
	 * @param int $order_id Order id (defaults to the current order on the thank-you page).
	 */
	public function __construct( int $order_id = 0 ) {
		$this->order_id = $order_id > 0 ? $order_id : self::resolve_order_id();

		if ( $this->order_id > 0 ) {
			$this->params = ProductData::for_order( $this->order_id );
		}
	}

	public function get_name(): string {
		return 'Purchase';
	}

	public function should_fire(): bool {
		if ( $this->order_id <= 0 || [] === $this->params ) {
			return false;
		}

		$order = $this->order();
		if ( null !== $order && ! self::counts( $order ) ) {
			return false;
		}

		if ( $this->already_tracked() ) {
			return false;
		}

		return parent::should_fire();
	}

	/**
	 * Whether the order is a purchase (not failed or cancelled).
	 *
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	public static function counts( \WC_Order $order ): bool {
		return ! $order->has_status( self::NOT_A_PURCHASE );
	}

	protected function option_key(): string {
		return 'woo_purchase';
	}

	/**
	 * Resolve the order id from the thank-you page query.
	 *
	 * @return int
	 */
	private static function resolve_order_id(): int {
		global $wp;

		if ( isset( $wp->query_vars['order-received'] ) ) {
			return (int) $wp->query_vars['order-received'];
		}

		return 0;
	}

	/**
	 * Whether this order has already had Purchase fired (idempotency on refreshes).
	 *
	 * @return bool
	 */
	private function already_tracked(): bool {
		$order = $this->order();

		return null !== $order && (bool) $order->get_meta( self::TRACKED_META, true );
	}

	/**
	 * Mark the order as tracked.
	 *
	 * Called only once the event has actually been printed into the page
	 * (see EventManager::queue()'s $on_emit), so a Purchase that never
	 * reached the browser is retried on the next thank-you page view.
	 *
	 * @return void
	 */
	public function mark_tracked(): void {
		$order = $this->order();

		if ( null !== $order ) {
			$order->update_meta_data( self::TRACKED_META, '1' );
			$order->save();
		}
	}

	/**
	 * The order (via the WC CRUD API, HPOS-safe).
	 *
	 * @return \WC_Order|null
	 */
	private function order(): ?\WC_Order {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $this->order_id ) : false;

		return $order instanceof \WC_Order ? $order : null;
	}
}

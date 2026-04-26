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

		if ( $this->already_tracked() ) {
			return false;
		}

		if ( ! parent::should_fire() ) {
			return false;
		}

		$this->mark_tracked();
		return true;
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
		return (bool) get_post_meta( $this->order_id, '_lw_pixel_purchase_tracked', true );
	}

	/**
	 * Mark the order as tracked.
	 *
	 * @return void
	 */
	private function mark_tracked(): void {
		update_post_meta( $this->order_id, '_lw_pixel_purchase_tracked', '1' );
	}
}

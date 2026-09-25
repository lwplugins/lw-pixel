<?php
/**
 * Server-side Purchase, once per order and provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Events\EventId;
use LightweightPlugins\Pixel\WooCommerce\Events\Purchase;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Sends an order's Purchase to every active server-side provider the
 * customer consented to at checkout, with the order's deterministic event
 * id (the browser Purchase uses the same one).
 *
 * Each provider is marked on the order only after it accepted the event,
 * so a failed provider is retried on the next trigger while the others are
 * never sent twice. The check → send → mark sequence runs under a per-order
 * lock (payment webhook and checkout request can race).
 */
final class ServerPurchase {

	/**
	 * Order meta key marking a provider's Purchase as sent. Meta keeps its
	 * pre-1.3.0 key so orders already sent are not sent again.
	 *
	 * @param string $provider Provider id.
	 * @return string
	 */
	public static function tracked_meta( string $provider ): string {
		return 'fb' === $provider ? '_lw_pixel_capi_purchase_tracked' : '_lw_pixel_server_purchase_' . $provider;
	}

	/**
	 * Send the Purchase to the given providers (all active ones when empty).
	 *
	 * @param int                $order_id Order id.
	 * @param array<int, string> $only     Provider ids to limit to.
	 * @return void
	 */
	public static function send( int $order_id, array $only = [] ): void {
		if ( $order_id <= 0 || ! OrderLock::acquire( $order_id ) ) {
			return;
		}

		try {
			self::send_locked( $order_id, $only );
		} finally {
			OrderLock::release( $order_id );
		}
	}

	/**
	 * Check → send → mark, while holding the order lock.
	 *
	 * @param int                $order_id Order id.
	 * @param array<int, string> $only     Provider ids to limit to.
	 * @return void
	 */
	private static function send_locked( int $order_id, array $only ): void {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
		// Queued earlier, the order may have failed or been cancelled since.
		if ( ! $order instanceof \WC_Order || ! Purchase::counts( $order ) ) {
			return;
		}

		$params = null;

		foreach ( ServerProviders::active() as $id => $provider ) {
			if ( ( [] !== $only && ! in_array( $id, $only, true ) ) || $order->get_meta( self::tracked_meta( $id ), true ) ) {
				continue;
			}

			// Only the context captured during the customer's own checkout
			// is used, and only with their consent for this provider. Without
			// it (consent refused, order created in wp-admin or via the REST
			// API, placed before this was recorded) nothing is sent.
			$checkout = CheckoutContext::get( $order, $id );
			if ( [] === $checkout ) {
				continue;
			}

			$params = $params ?? ProductData::for_order( $order_id );
			if ( [] === $params ) {
				return;
			}

			$context = RequestContext::from_checkout( $checkout, CustomerData::from_order( $order ) );
			$event   = $provider->build( 'Purchase', $params, EventId::for_order( $order_id ), $context );

			if ( null === $event ) {
				continue;
			}

			$result = $provider->send( [ $event ] );
			DispatchQueue::report( $id, $result['ok'], $result['status'] );

			if ( $result['ok'] ) {
				$order->update_meta_data( self::tracked_meta( $id ), '1' );
				$order->save();
			}
		}
	}
}

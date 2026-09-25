<?php
/**
 * Server-side Purchase, once per order and provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Events\EventId;
use LightweightPlugins\Pixel\Server\Providers\ServerProviderInterface;
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

		$params  = null;
		$pending = false;

		foreach ( ServerProviders::active() as $id => $provider ) {
			// Only the context captured during the customer's own checkout
			// is used, and only with their consent for this provider. Without
			// it (consent refused, order created in wp-admin or via the REST
			// API, placed before this was recorded) nothing is sent.
			if ( $order->get_meta( self::tracked_meta( $id ), true ) || [] === CheckoutContext::get( $order, $id ) ) {
				continue;
			}

			if ( [] !== $only && ! in_array( $id, $only, true ) ) {
				$pending = true;
				continue;
			}

			$params = $params ?? ProductData::for_order( $order_id );
			if ( [] === $params ) {
				return;
			}

			$pending = ! self::send_one( $order, $id, $provider, $params ) || $pending;
		}

		if ( ! $pending ) {
			self::forget_context( $order );
		}
	}

	/**
	 * Build and send one provider's Purchase.
	 *
	 * @param \WC_Order               $order    Order.
	 * @param string                  $id       Provider id.
	 * @param ServerProviderInterface $provider Provider.
	 * @param array<string, mixed>    $params   Generic Purchase params.
	 * @return bool True when nothing is left to send for this provider
	 *              (accepted, or the provider does not take this Purchase).
	 */
	private static function send_one( \WC_Order $order, string $id, ServerProviderInterface $provider, array $params ): bool {
		$context = RequestContext::from_checkout( CheckoutContext::get( $order, $id ), CustomerData::from_order( $order ) );
		$event   = $provider->build( 'Purchase', $params, EventId::for_order( (int) $order->get_id() ), $context );

		if ( null === $event ) {
			return true;
		}

		$result = $provider->send( [ $event ] );
		DispatchQueue::report( $id, $result['ok'], $result['status'] );

		if ( $result['ok'] ) {
			$order->update_meta_data( self::tracked_meta( $id ), '1' );
			$order->save();
		}

		return $result['ok'];
	}

	/**
	 * Every provider is done: the checkout context (IP, user agent, browser
	 * identifiers) is no longer needed, so it is not kept.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	private static function forget_context( \WC_Order $order ): void {
		if ( '' !== $order->get_meta( CheckoutContext::META_KEY, true ) ) {
			$order->delete_meta_data( CheckoutContext::META_KEY );
			$order->save();
		}
	}
}

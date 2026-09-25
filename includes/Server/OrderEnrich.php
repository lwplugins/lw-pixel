<?php
/**
 * WooCommerce order enrichment — fires CAPI Purchase events when an order's
 * status changes (e.g. pending → completed) so the pixel team gets the final
 * payment value rather than the intermediate state captured at thank-you time.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\AdvancedMatching\UserDataBuilder;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Listens for WC order completion / processing and dispatches a Purchase to CAPI.
 *
 * The status change often happens inside the checkout/payment request, so
 * the HTTP call to Meta is handed to Action Scheduler (bundled with
 * WooCommerce) instead of blocking the customer. Only when Action Scheduler
 * is unavailable is it sent inline.
 */
final class OrderEnrich {

	public const ASYNC_HOOK = 'lw_pixel_capi_send_purchase';

	private const TRACKED_META = '_lw_pixel_capi_purchase_tracked';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! Options::get( 'fb_capi_enabled' ) || ! Options::get( 'fb_order_enrich' ) ) {
			return;
		}

		CheckoutContext::register();

		add_action( 'woocommerce_order_status_completed', [ self::class, 'enrich' ] );
		add_action( 'woocommerce_order_status_processing', [ self::class, 'enrich' ] );
		add_action( self::ASYNC_HOOK, [ self::class, 'send' ] );
	}

	/**
	 * Queue the CAPI Purchase for the given order.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public static function enrich( int $order_id ): void {
		if ( $order_id <= 0 ) {
			return;
		}

		// Unique: the processing → completed pair queues a single action.
		if ( function_exists( 'as_enqueue_async_action' )
			&& as_enqueue_async_action( self::ASYNC_HOOK, [ $order_id ], 'lw-pixel', true ) > 0 ) {
			return;
		}

		self::send( $order_id );
	}

	/**
	 * Send the CAPI Purchase for the given order, once.
	 *
	 * The order is marked tracked only when Meta accepted the event, so a
	 * failed send is retried on the next status change.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public static function send( int $order_id ): void {
		if ( $order_id <= 0 || ! OrderLock::acquire( $order_id ) ) {
			return;
		}

		try {
			self::send_locked( $order_id );
		} finally {
			OrderLock::release( $order_id );
		}
	}

	/**
	 * Check → send → mark, while holding the order lock.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	private static function send_locked( int $order_id ): void {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
		if ( ! $order instanceof \WC_Order || $order->get_meta( self::TRACKED_META, true ) ) {
			return;
		}

		// Only the context captured during the customer's own checkout is
		// sent, and only when they consented to the Meta pixel there. Without
		// it (consent refused, order created in wp-admin or via the REST API,
		// or placed before this was recorded) nothing is sent: there is no
		// consent on record, and the current request belongs to someone else.
		$context = CheckoutContext::get( $order );
		if ( [] === $context ) {
			return;
		}

		$params = ProductData::for_order( $order_id );
		if ( [] === $params ) {
			return;
		}

		$result = FacebookCAPI::send_server_event(
			'Purchase',
			self::custom_data( $params ),
			array_merge( UserDataBuilder::for_order( $order_id ), CheckoutContext::to_user_data( $context ) ),
			$context['url'] ?? ''
		);

		if ( $result['ok'] ) {
			$order->update_meta_data( self::TRACKED_META, '1' );
			$order->save();
		}
	}

	/**
	 * Build the custom_data block for the Meta CAPI Purchase event.
	 *
	 * @param array<string, mixed> $params Order params.
	 * @return array<string, mixed>
	 */
	private static function custom_data( array $params ): array {
		$contents = [];

		foreach ( (array) ( $params['contents'] ?? [] ) as $item ) {
			$contents[] = [
				'id'         => $item['content_id'] ?? '',
				'quantity'   => (int) ( $item['quantity'] ?? 1 ),
				'item_price' => (float) ( $item['price'] ?? 0 ),
			];
		}

		return [
			'currency'     => $params['currency'] ?? 'USD',
			'value'        => (float) ( $params['value'] ?? 0 ),
			'order_id'     => (string) ( $params['order_id'] ?? '' ),
			'num_items'    => (int) ( $params['num_items'] ?? count( $contents ) ),
			'contents'     => $contents,
			'content_type' => 'product',
		];
	}
}

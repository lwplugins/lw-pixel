<?php
/**
 * WooCommerce hooks for the server-side Purchase.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Options;

/**
 * When the Purchase is sent from the server:
 * - GA4 Measurement Protocol and ChatGPT Ads Conversions API: when the
 *   order reaches processing or completed (payment confirmed, final value).
 * - Meta Conversions API: the same with "Re-send Purchase when the order
 *   completes/processes" (`fb_order_enrich`) on; otherwise on the thank-you
 *   page, together with the browser Purchase.
 *
 * The HTTP calls are handed to Action Scheduler (bundled with WooCommerce)
 * so checkout is never slowed down; inline only when it is unavailable.
 */
final class OrderEnrich {

	public const ASYNC_HOOK = 'lw_pixel_capi_send_purchase';

	public const TRIGGER_STATUS   = 'status';
	public const TRIGGER_THANKYOU = 'thankyou';

	/**
	 * Register hooks when any server-side provider is active.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( [] === ServerProviders::active() ) {
			return;
		}

		CheckoutContext::register();

		add_action( 'woocommerce_order_status_completed', [ self::class, 'enrich' ] );
		add_action( 'woocommerce_order_status_processing', [ self::class, 'enrich' ] );
		add_action( 'woocommerce_thankyou', [ self::class, 'thankyou' ] );
		add_action( self::ASYNC_HOOK, [ self::class, 'send' ], 10, 2 );
	}

	/**
	 * Order status reached processing/completed.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public static function enrich( int $order_id ): void {
		self::queue( $order_id, self::TRIGGER_STATUS );
	}

	/**
	 * The customer's thank-you page.
	 *
	 * @param mixed $order_id Order id.
	 * @return void
	 */
	public static function thankyou( $order_id ): void {
		if ( ! Options::get( 'fb_order_enrich' ) ) {
			self::queue( (int) $order_id, self::TRIGGER_THANKYOU );
		}
	}

	/**
	 * Queue (or, without Action Scheduler, run) a send.
	 *
	 * @param int    $order_id Order id.
	 * @param string $trigger  TRIGGER_* constant.
	 * @return void
	 */
	private static function queue( int $order_id, string $trigger ): void {
		if ( $order_id <= 0 ) {
			return;
		}

		// The status trigger keeps the pre-1.3.0 arguments, so the
		// processing → completed pair still queues a single (unique) action.
		$args = self::TRIGGER_STATUS === $trigger ? [ $order_id ] : [ $order_id, $trigger ];

		if ( function_exists( 'as_enqueue_async_action' )
			&& as_enqueue_async_action( self::ASYNC_HOOK, $args, 'lw-pixel', true ) > 0 ) {
			return;
		}

		self::send( $order_id, $trigger );
	}

	/**
	 * Send the Purchase to the providers that belong to this trigger.
	 *
	 * @param int    $order_id Order id.
	 * @param string $trigger  TRIGGER_* constant.
	 * @return void
	 */
	public static function send( int $order_id, string $trigger = self::TRIGGER_STATUS ): void {
		$providers = self::providers_for( $trigger );

		if ( [] !== $providers ) {
			ServerPurchase::send( $order_id, $providers );
		}
	}

	/**
	 * Provider ids sending the Purchase on a trigger.
	 *
	 * @param string $trigger TRIGGER_* constant.
	 * @return array<int, string>
	 */
	public static function providers_for( string $trigger ): array {
		$meta_on_status = (bool) Options::get( 'fb_order_enrich' );
		$ids            = [];

		foreach ( array_keys( ServerProviders::active() ) as $id ) {
			$on_status = 'fb' !== $id || $meta_on_status;

			if ( ( self::TRIGGER_STATUS === $trigger ) === $on_status ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}
}

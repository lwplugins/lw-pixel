<?php
/**
 * Captures the customer's browser context on the order at checkout time.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

/**
 * Stores IP, user agent, `_fbp`, `_fbc` and the source URL on the order while
 * the customer's own checkout request is running, so the Conversion API
 * Purchase later sends the customer's data — never that of the admin or
 * payment webhook that moves the order to processing/completed.
 *
 * Written through the WC order CRUD API, so it works with HPOS.
 */
final class CheckoutContext {

	public const META_KEY = '_lw_pixel_capi_context';

	/**
	 * Register checkout hooks (classic shortcode checkout + Store API / block checkout).
	 *
	 * Both fire in the customer's request, after the order exists and before
	 * the payment is processed (so before the status reaches processing).
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'woocommerce_checkout_order_processed', [ self::class, 'capture_classic' ], 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ self::class, 'capture' ] );
	}

	/**
	 * Classic checkout adapter.
	 *
	 * @param mixed $order_id    Order id (unused).
	 * @param mixed $posted_data Posted checkout data (unused).
	 * @param mixed $order       Order object.
	 * @return void
	 */
	public static function capture_classic( $order_id, $posted_data, $order ): void {
		unset( $order_id, $posted_data );

		if ( $order instanceof \WC_Order ) {
			self::capture( $order );
		}
	}

	/**
	 * Store the current (customer) request context on the order.
	 *
	 * @param mixed $order Order object.
	 * @return void
	 */
	public static function capture( $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$order->update_meta_data( self::META_KEY, self::from_request() );
		$order->save();
	}

	/**
	 * Build the context from the current request.
	 *
	 * @return array{ip: string, ua: string, fbp: string, fbc: string, url: string}
	 */
	public static function from_request(): array {
		return [
			'ip'  => ClientRequest::ip(),
			'ua'  => ClientRequest::user_agent(),
			'fbp' => ClientRequest::cookie( '_fbp' ),
			'fbc' => ClientRequest::cookie( '_fbc' ),
			'url' => function_exists( 'wc_get_checkout_url' ) ? (string) wc_get_checkout_url() : home_url( '/' ),
		];
	}

	/**
	 * The stored context, or an empty array when none was captured.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<string, string>
	 */
	public static function get( \WC_Order $order ): array {
		$context = $order->get_meta( self::META_KEY, true );

		if ( ! is_array( $context ) || empty( $context['ua'] ) ) {
			return [];
		}

		return array_map( 'strval', $context );
	}

	/**
	 * Meta CAPI user_data fields (unhashed, per Meta's spec) from a stored context.
	 *
	 * @param array<string, string> $context Stored context.
	 * @return array<string, string>
	 */
	public static function to_user_data( array $context ): array {
		return array_filter(
			[
				'client_ip_address' => $context['ip'] ?? '',
				'client_user_agent' => $context['ua'] ?? '',
				'fbp'               => $context['fbp'] ?? '',
				'fbc'               => $context['fbc'] ?? '',
			]
		);
	}
}

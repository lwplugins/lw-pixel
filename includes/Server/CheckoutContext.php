<?php
/**
 * Captures the customer's browser context on the order at checkout time.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;

/**
 * Stores IP, user agent, the source URL and the consented providers' browser
 * identifiers (`_fbp`/`_fbc`, the GA4 client id, ChatGPT Ads `__obref`/
 * `__oppref`) on the order while
 * the customer's own checkout request is running, so the Conversion API
 * Purchase later sends the customer's data — never that of the admin or
 * payment webhook that moves the order to processing/completed.
 *
 * The visitor's consent for each server-side provider is recorded at the
 * same time. It is resolved exactly like the browser pixel's gating: through LW Cookie
 * when it is active (it reads the visitor's consent cookie, which the
 * checkout request carries), otherwise through the
 * `lw_pixel_is_category_allowed` filter, which defaults to allowed — the
 * same default under which the browser pixels fire. When consent is not
 * given, only the refusal is stored: no IP, user agent or cookies.
 *
 * Written through the WC order CRUD API, so it works with HPOS.
 */
final class CheckoutContext {

	public const META_KEY = '_lw_pixel_capi_context';

	public const GRANTED = 'granted';
	public const DENIED  = 'denied';

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

		$consents = self::consents( $order );
		$granted  = array_keys( array_filter( $consents ) );
		$context  = [ 'consent' => ! empty( $consents['fb'] ) ? self::GRANTED : self::DENIED ];

		if ( [] !== $granted ) {
			$context = array_merge( $context, self::from_request( $granted ) );
		}

		if ( [ 'fb' ] !== array_keys( $consents ) ) {
			$context['consents'] = array_map(
				static fn ( bool $allowed ): string => $allowed ? self::GRANTED : self::DENIED,
				$consents
			);
		}

		$order->update_meta_data( self::META_KEY, $context );
		$order->save();
	}

	/**
	 * Consent of the visitor placing the order, per server-side provider.
	 *
	 * @param \WC_Order $order Order being placed.
	 * @return array<string, bool>
	 */
	private static function consents( \WC_Order $order ): array {
		$manager  = new ConsentManager();
		$ids      = array_keys( ServerProviders::active() );
		$ids      = [] === $ids ? [ 'fb' ] : $ids;
		$consents = [];

		foreach ( $ids as $id ) {
			$allowed = $manager->is_pixel_allowed( $id );

			if ( 'fb' === $id ) {
				/**
				 * Filter whether the server-side Meta Purchase may be sent for this order.
				 *
				 * Evaluated once, in the customer's checkout request.
				 *
				 * @param bool      $allowed Consent state resolved like the browser pixel.
				 * @param \WC_Order $order   Order being placed.
				 */
				$allowed = (bool) apply_filters( 'lw_pixel_capi_purchase_consent', $allowed, $order );
			}

			/**
			 * Filter whether a provider's server-side Purchase may be sent for this order.
			 *
			 * @param bool      $allowed  Consent state resolved like the browser pixel.
			 * @param \WC_Order $order    Order being placed.
			 * @param string    $provider Provider id ('fb', 'ga4', 'chatgpt').
			 */
			$consents[ $id ] = (bool) apply_filters( 'lw_pixel_server_purchase_consent', $allowed, $order, $id );
		}

		return $consents;
	}

	/**
	 * Build the context from the current request. Browser identifiers are
	 * kept only for the providers the visitor consented to.
	 *
	 * @param array<int, string> $granted Provider ids with consent (default: Meta).
	 * @return array<string, string>
	 */
	public static function from_request( array $granted = [ 'fb' ] ): array {
		$context = [
			'ip' => ClientRequest::ip(),
			'ua' => ClientRequest::user_agent(),
		];

		if ( in_array( 'fb', $granted, true ) ) {
			$context['fbp'] = ClientRequest::cookie( '_fbp' );
			$context['fbc'] = ClientRequest::cookie( '_fbc' );
		}

		if ( in_array( 'ga4', $granted, true ) ) {
			$context['ga'] = RequestContext::ga_client_id( ClientRequest::cookie( '_ga' ) );
		}

		if ( in_array( 'chatgpt', $granted, true ) ) {
			$context['obref']  = ClientRequest::cookie( '__obref' );
			$context['oppref'] = ClientRequest::cookie( '__oppref' );
		}

		$context['url'] = function_exists( 'wc_get_checkout_url' ) ? (string) wc_get_checkout_url() : home_url( '/' );

		return $context;
	}

	/**
	 * The stored context for a provider, or an empty array when none was
	 * captured or the customer did not consent to that provider.
	 *
	 * Orders placed before 1.3.0 carry only the Meta decision (`consent`).
	 *
	 * @param \WC_Order $order    Order.
	 * @param string    $provider Provider id.
	 * @return array<string, string>
	 */
	public static function get( \WC_Order $order, string $provider = 'fb' ): array {
		$context = $order->get_meta( self::META_KEY, true );

		if ( ! is_array( $context ) || empty( $context['ua'] ) ) {
			return [];
		}

		$consents = isset( $context['consents'] ) && is_array( $context['consents'] ) ? $context['consents'] : [];
		$state    = $consents[ $provider ] ?? ( 'fb' === $provider ? ( $context['consent'] ?? '' ) : '' );

		if ( self::GRANTED !== $state ) {
			return [];
		}

		unset( $context['consents'] );

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

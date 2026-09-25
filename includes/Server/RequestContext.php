<?php
/**
 * The visitor context a server-side event is sent with.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

/**
 * One array shape for every provider:
 * time (unix seconds), url, ip, ua, fbp, fbc, ga (GA4 client id), obref,
 * oppref (ChatGPT Ads cookies) and customer (raw fields, see CustomerData).
 */
final class RequestContext {

	/**
	 * Context of the visitor making the current request.
	 *
	 * @return array<string, mixed>
	 */
	public static function current(): array {
		return [
			'time'     => time(),
			'url'      => self::current_url(),
			'ip'       => ClientRequest::ip(),
			'ua'       => ClientRequest::user_agent(),
			'fbp'      => ClientRequest::cookie( '_fbp' ),
			'fbc'      => ClientRequest::cookie( '_fbc' ),
			'ga'       => self::ga_client_id( ClientRequest::cookie( '_ga' ) ),
			'obref'    => ClientRequest::cookie( '__obref' ),
			'oppref'   => ClientRequest::cookie( '__oppref' ),
			'customer' => CustomerData::current_user(),
		];
	}

	/**
	 * Context of an order's purchase: the request data captured during the
	 * customer's own checkout plus the order's billing customer.
	 *
	 * @param array<string, string> $checkout Stored checkout context.
	 * @param array<string, string> $customer Raw customer data.
	 * @return array<string, mixed>
	 */
	public static function from_checkout( array $checkout, array $customer ): array {
		$context = [
			'time'     => time(),
			'customer' => $customer,
		];

		foreach ( [ 'url', 'ip', 'ua', 'fbp', 'fbc', 'ga', 'obref', 'oppref' ] as $key ) {
			$context[ $key ] = (string) ( $checkout[ $key ] ?? '' );
		}

		return $context;
	}

	/**
	 * GA4 client id from the `_ga` cookie ("GA1.1.123.456" → "123.456").
	 *
	 * @param string $cookie Cookie value.
	 * @return string
	 */
	public static function ga_client_id( string $cookie ): string {
		$parts = explode( '.', $cookie );

		if ( count( $parts ) < 4 || ! ctype_digit( $parts[2] ) || ! ctype_digit( $parts[3] ) ) {
			return '';
		}

		return $parts[2] . '.' . $parts[3];
	}

	/**
	 * Absolute URL of the current request.
	 *
	 * @return string
	 */
	private static function current_url(): string {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return home_url( '/' );
		}

		return home_url( esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) );
	}
}

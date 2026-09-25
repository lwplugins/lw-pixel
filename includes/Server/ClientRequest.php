<?php
/**
 * Reads visitor identifiers (IP, user agent, Meta cookies) from the current request.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

/**
 * Request-scoped visitor data used by the Conversion API.
 *
 * Only call this while handling the visitor's own request (a page view or
 * their checkout). In an admin, cron or webhook request these values belong
 * to someone else.
 */
final class ClientRequest {

	/**
	 * Resolve the client IP address.
	 *
	 * Defaults to REMOTE_ADDR (which the client cannot forge). Sites behind a
	 * trusted reverse proxy (Cloudflare, nginx, …) can opt into proxy headers
	 * with the `lw_pixel_trust_proxy_headers` filter — only enable this when
	 * the webserver strips inbound forged headers, otherwise an attacker can
	 * spoof their IP toward the Conversion API.
	 *
	 * @return string
	 */
	public static function ip(): string {
		$keys = [ 'REMOTE_ADDR' ];

		if ( (bool) apply_filters( 'lw_pixel_trust_proxy_headers', false ) ) {
			$keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];
		}

		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}

			$raw   = sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) );
			$value = explode( ',', $raw )[0];
			$ip    = trim( $value );

			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}

		return '';
	}

	/**
	 * The client's user agent.
	 *
	 * @return string
	 */
	public static function user_agent(): string {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) );
	}

	/**
	 * A sanitized cookie value, or an empty string.
	 *
	 * @param string $name Cookie name (e.g. `_fbp`).
	 * @return string
	 */
	public static function cookie( string $name ): string {
		if ( empty( $_COOKIE[ $name ] ) || ! is_string( $_COOKIE[ $name ] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) );
	}
}

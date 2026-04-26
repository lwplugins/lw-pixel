<?php
/**
 * Meta Conversion API server-side dispatcher.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\AdvancedMatching\UserDataBuilder;
use LightweightPlugins\Pixel\Options;

/**
 * Sends events to the Meta Conversion API endpoint.
 *
 * Reference: https://developers.facebook.com/docs/marketing-api/conversions-api
 */
final class FacebookCAPI {

	private const API_VERSION = 'v18.0';
	private const API_URL     = 'https://graph.facebook.com/%s/%s/events';

	/**
	 * Send a single event.
	 *
	 * @param string               $event_name      Event name (e.g. "Purchase").
	 * @param array<string, mixed> $custom_data     Custom data block.
	 * @param array<string, mixed> $user_data       User data block (hashed values).
	 * @param int|null             $event_timestamp Unix timestamp (defaults to now).
	 * @return array{ok: bool, body: string}
	 */
	public static function send_event( string $event_name, array $custom_data = [], array $user_data = [], ?int $event_timestamp = null ): array {
		$pixel_id = (string) Options::get( 'fb_pixel_id', '' );
		$token    = (string) Options::get( 'fb_capi_token', '' );

		if ( '' === $pixel_id || '' === $token ) {
			return [
				'ok'   => false,
				'body' => 'CAPI not configured.',
			];
		}

		$event = [
			'event_name'       => $event_name,
			'event_time'       => $event_timestamp ?? time(),
			'action_source'    => 'website',
			'event_source_url' => self::current_url(),
			'user_data'        => (array) apply_filters(
				'lw_pixel_capi_user_data',
				self::merge_user_data( $user_data )
			),
			'custom_data'      => $custom_data,
		];

		$event = (array) apply_filters( 'lw_pixel_capi_event_body', $event, $event_name );

		$test_code = (string) Options::get( 'fb_test_event_code', '' );
		$body      = [
			'data'         => [ $event ],
			'access_token' => $token,
		];

		if ( '' !== $test_code ) {
			$body['test_event_code'] = $test_code;
		}

		$response = wp_remote_post(
			sprintf( self::API_URL, self::API_VERSION, $pixel_id ),
			[
				'headers'  => [ 'Content-Type' => 'application/json' ],
				'body'     => wp_json_encode( $body ),
				'timeout'  => 5,
				'blocking' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'   => false,
				'body' => $response->get_error_message(),
			];
		}

		$code          = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		return [
			'ok'   => 200 === $code,
			'body' => is_string( $response_body ) ? $response_body : '',
		];
	}

	/**
	 * Merge default user data (IP, UA, fbp, fbc) with caller-supplied values.
	 *
	 * @param array<string, mixed> $extra Caller user data (already hashed).
	 * @return array<string, mixed>
	 */
	private static function merge_user_data( array $extra ): array {
		$base = [
			'client_ip_address' => self::client_ip(),
			'client_user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
				? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) )
				: '',
		];

		if ( ! empty( $_COOKIE['_fbp'] ) ) {
			$base['fbp'] = sanitize_text_field( wp_unslash( (string) $_COOKIE['_fbp'] ) );
		}

		if ( ! empty( $_COOKIE['_fbc'] ) ) {
			$base['fbc'] = sanitize_text_field( wp_unslash( (string) $_COOKIE['_fbc'] ) );
		}

		// Advanced matching: hashed user-data from logged-in users.
		$advanced = UserDataBuilder::for_current_request();

		return array_filter( array_merge( $base, $advanced, $extra ) );
	}

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
	private static function client_ip(): string {
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
	 * Resolve the current URL.
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

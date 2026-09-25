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

	/**
	 * Graph API version (v18.0 expired on 2026-01-26). v26.0 checked against
	 * Meta's version table and the Conversions API "Using the API" docs on
	 * 2026-09-26; the /events payload used here is unchanged.
	 * Filterable via `lw_pixel_capi_api_version`.
	 */
	public const API_VERSION = 'v26.0';

	private const API_URL = 'https://graph.facebook.com/%s/%s/events';

	/**
	 * Send a single event on behalf of the visitor making the current request.
	 *
	 * Merges the current request's IP, user agent, `_fbp`/`_fbc` cookies and
	 * the logged-in user's advanced-matching data into user_data. Do NOT use
	 * this for order events — see send_server_event().
	 *
	 * @param string               $event_name      Event name (e.g. "Purchase").
	 * @param array<string, mixed> $custom_data     Custom data block.
	 * @param array<string, mixed> $user_data       User data block (hashed values).
	 * @param int|null             $event_timestamp Unix timestamp (defaults to now).
	 * @return array{ok: bool, body: string}
	 */
	public static function send_event( string $event_name, array $custom_data = [], array $user_data = [], ?int $event_timestamp = null ): array {
		return self::dispatch( $event_name, $custom_data, self::merge_user_data( $user_data ), self::current_url(), $event_timestamp );
	}

	/**
	 * Send an event whose user data was captured earlier (e.g. at checkout).
	 *
	 * Nothing is read from the current request or the logged-in user: this
	 * runs from order status changes, which can happen in an admin, cron or
	 * payment-webhook request that has nothing to do with the customer.
	 *
	 * @param string               $event_name      Event name.
	 * @param array<string, mixed> $custom_data     Custom data block.
	 * @param array<string, mixed> $user_data       Complete user data block.
	 * @param string               $source_url      Event source URL.
	 * @param int|null             $event_timestamp Unix timestamp (defaults to now).
	 * @return array{ok: bool, body: string}
	 */
	public static function send_server_event( string $event_name, array $custom_data, array $user_data, string $source_url, ?int $event_timestamp = null ): array {
		return self::dispatch( $event_name, $custom_data, array_filter( $user_data ), $source_url, $event_timestamp );
	}

	/**
	 * Build the event body and POST it to the Graph API.
	 *
	 * @param string               $event_name      Event name.
	 * @param array<string, mixed> $custom_data     Custom data block.
	 * @param array<string, mixed> $user_data       Final user data block.
	 * @param string               $source_url      Event source URL.
	 * @param int|null             $event_timestamp Unix timestamp (defaults to now).
	 * @return array{ok: bool, body: string}
	 */
	private static function dispatch( string $event_name, array $custom_data, array $user_data, string $source_url, ?int $event_timestamp ): array {
		$event = [
			'event_name'       => $event_name,
			'event_time'       => $event_timestamp ?? time(),
			'action_source'    => 'website',
			'event_source_url' => $source_url,
			'user_data'        => (array) apply_filters( 'lw_pixel_capi_user_data', $user_data ),
			'custom_data'      => $custom_data,
		];

		$result = self::send_events( [ (array) apply_filters( 'lw_pixel_capi_event_body', $event, $event_name ) ] );

		return [
			'ok'   => $result['ok'],
			'body' => $result['body'],
		];
	}

	/**
	 * POST a batch of complete event bodies to the Graph API.
	 *
	 * @param array<int, array<string, mixed>> $events Event bodies.
	 * @return array{ok: bool, status: int, body: string}
	 */
	public static function send_events( array $events ): array {
		$pixel_id = (string) Options::get( 'fb_pixel_id', '' );
		$token    = (string) Options::get( 'fb_capi_token', '' );

		if ( '' === $pixel_id || '' === $token || [] === $events ) {
			return [
				'ok'     => false,
				'status' => 0,
				'body'   => 'CAPI not configured.',
			];
		}

		$body = [
			'data'         => array_values( $events ),
			'access_token' => $token,
		];

		$test_code = (string) Options::get( 'fb_test_event_code', '' );
		if ( '' !== $test_code ) {
			$body['test_event_code'] = $test_code;
		}

		$response = wp_remote_post(
			sprintf( self::API_URL, self::api_version(), rawurlencode( $pixel_id ) ),
			[
				'headers'  => [ 'Content-Type' => 'application/json' ],
				'body'     => wp_json_encode( $body ),
				'timeout'  => 5,
				'blocking' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'     => false,
				'status' => 0,
				'body'   => $response->get_error_message(),
			];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		return [
			'ok'     => 200 === $code,
			'status' => $code,
			'body'   => (string) wp_remote_retrieve_body( $response ),
		];
	}

	/**
	 * Merge the current request's user data (IP, UA, fbp, fbc) with caller-supplied values.
	 *
	 * @param array<string, mixed> $extra Caller user data (already hashed).
	 * @return array<string, mixed>
	 */
	private static function merge_user_data( array $extra ): array {
		$base = [
			'client_ip_address' => ClientRequest::ip(),
			'client_user_agent' => ClientRequest::user_agent(),
			'fbp'               => ClientRequest::cookie( '_fbp' ),
			'fbc'               => ClientRequest::cookie( '_fbc' ),
		];

		// Advanced matching: hashed user-data from logged-in users.
		$advanced = UserDataBuilder::for_current_request();

		return array_filter( array_merge( $base, $advanced, $extra ) );
	}

	/**
	 * The Graph API version to call (e.g. "v26.0").
	 *
	 * @return string
	 */
	public static function api_version(): string {
		$version = (string) apply_filters( 'lw_pixel_capi_api_version', self::API_VERSION );

		return 1 === preg_match( '/^v\d+\.\d+$/', $version ) ? $version : self::API_VERSION;
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

<?php
/**
 * Pinterest Conversions API client.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Events\EventId;
use LightweightPlugins\Pixel\Options;

/**
 * POST https://api.pinterest.com/v5/ad_accounts/{ad_account_id}/events with
 * `Authorization: Bearer <conversion access token>` and `{"data": [...]}`
 * (Pinterest API v5 SDK docs, checked 2026-11-01). `?test=true` makes
 * Pinterest validate the events without recording them.
 *
 * The token never leaves the server: it is not printed, logged or
 * returned. It can be defined as LW_PIXEL_PINTEREST_TOKEN in wp-config.php
 * instead of being stored in the database.
 */
final class PinterestCAPI {

	private const ENDPOINT = 'https://api.pinterest.com/v5/ad_accounts/%s/events';

	/**
	 * Max events per request (API limit).
	 */
	public const MAX_BATCH = 1000;

	/**
	 * The conversion access token: the wp-config constant wins over the
	 * stored option.
	 *
	 * @return string
	 */
	public static function token(): string {
		if ( defined( 'LW_PIXEL_PINTEREST_TOKEN' ) && '' !== (string) constant( 'LW_PIXEL_PINTEREST_TOKEN' ) ) {
			return (string) constant( 'LW_PIXEL_PINTEREST_TOKEN' );
		}

		return (string) Options::get( 'pinterest_capi_token', '' );
	}

	/**
	 * The saved ad account id.
	 *
	 * @return string
	 */
	public static function ad_account_id(): string {
		return trim( (string) Options::get( 'pinterest_ad_account_id', '' ) );
	}

	/**
	 * Whether an ad account id and a token are both available.
	 *
	 * @return bool
	 */
	public static function is_configured(): bool {
		return '' !== self::ad_account_id() && '' !== self::token();
	}

	/**
	 * Endpoint for the saved ad account, with `test=true` in test mode.
	 *
	 * @param bool $test Validate only (nothing is recorded).
	 * @return string
	 */
	public static function endpoint( bool $test ): string {
		$url = sprintf( self::ENDPOINT, rawurlencode( self::ad_account_id() ) );

		return $test ? $url . '?test=true' : $url;
	}

	/**
	 * Send a batch of complete event bodies.
	 *
	 * @param array<int, array<string, mixed>> $events Event bodies.
	 * @param bool                             $test   Force test mode (connection test).
	 * @return array{ok: bool, status: int}
	 */
	public static function send( array $events, bool $test = false ): array {
		if ( ! self::is_configured() || [] === $events ) {
			return [
				'ok'     => false,
				'status' => 0,
			];
		}

		$response = wp_remote_post(
			self::endpoint( $test || (bool) Options::get( 'pinterest_capi_test' ) ),
			[
				'headers'  => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::token(),
				],
				'body'     => wp_json_encode( [ 'data' => array_slice( array_values( $events ), 0, self::MAX_BATCH ) ] ),
				'timeout'  => 5,
				'blocking' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'     => false,
				'status' => 0,
			];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// 422: "Not all events were successfully processed".
		return [
			'ok'     => 200 === $code,
			'status' => $code,
		];
	}

	/**
	 * Check the saved ad account id + token with a `test=true` request
	 * (Pinterest validates the event without recording it).
	 *
	 * @return array{ok: bool, status: int, message: string}
	 */
	public static function test_connection(): array {
		if ( ! self::is_configured() ) {
			return [
				'ok'      => false,
				'status'  => 0,
				'message' => __( 'Save a Pinterest ad account ID and conversion access token first.', 'lw-pixel' ),
			];
		}

		$result = self::send(
			[
				[
					'event_name'       => 'page_visit',
					'action_source'    => 'web',
					'event_time'       => time(),
					'event_id'         => EventId::generate(),
					'event_source_url' => home_url( '/' ),
					'user_data'        => [
						'client_ip_address' => '192.0.2.1',
						'client_user_agent' => 'LW Pixel connection test',
					],
				],
			],
			true
		);

		return $result + [ 'message' => self::describe( $result ) ];
	}

	/**
	 * Human-readable result of a test request (no response body: it may
	 * echo request data).
	 *
	 * @param array{ok: bool, status: int} $result Result.
	 * @return string
	 */
	private static function describe( array $result ): string {
		if ( $result['ok'] ) {
			return __( 'Connected. Pinterest accepted a test event (test mode, nothing was recorded).', 'lw-pixel' );
		}

		if ( 401 === $result['status'] || 403 === $result['status'] ) {
			return __( 'Pinterest rejected the access token. Check the token and that it belongs to this ad account.', 'lw-pixel' );
		}

		if ( 0 === $result['status'] ) {
			return __( 'Could not reach the Pinterest API from this server.', 'lw-pixel' );
		}

		return sprintf(
			/* translators: %d: HTTP status code. */
			__( 'Pinterest returned HTTP %d. Check the ad account ID and access token.', 'lw-pixel' ),
			$result['status']
		);
	}
}

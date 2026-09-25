<?php
/**
 * GA4 Measurement Protocol server-side dispatcher.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Options;

/**
 * Sends events to GA4 via the Measurement Protocol.
 *
 * Reference: https://developers.google.com/analytics/devguides/collection/protocol/ga4
 */
final class GoogleAnalyticsMP {

	private const ENDPOINT = 'https://www.google-analytics.com/mp/collect';

	/**
	 * Send a single event.
	 *
	 * @param string               $event_name Event name (snake_case).
	 * @param array<string, mixed> $params     Event parameters.
	 * @param string|null          $client_id  Client ID (defaults to a hashed REMOTE_ADDR fallback).
	 * @return array{ok: bool, body: string}
	 */
	public static function send_event( string $event_name, array $params = [], ?string $client_id = null ): array {
		$result = self::send_events(
			$client_id ?? self::resolve_client_id(),
			[
				[
					'name'   => $event_name,
					'params' => $params,
				],
			]
		);

		return [
			'ok'   => $result['ok'],
			'body' => $result['body'],
		];
	}

	/**
	 * Send up to 25 events of one client.
	 *
	 * @param string                           $client_id GA4 client id.
	 * @param array<int, array<string, mixed>> $events    Events ({name, params}).
	 * @return array{ok: bool, status: int, body: string}
	 */
	public static function send_events( string $client_id, array $events ): array {
		$measurement_id = (string) Options::get( 'ga4_measurement_id', '' );
		$api_secret     = (string) Options::get( 'ga4_mp_api_secret', '' );

		if ( '' === $measurement_id || '' === $api_secret || '' === $client_id || [] === $events ) {
			return [
				'ok'     => false,
				'status' => 0,
				'body'   => 'GA4 MP not configured.',
			];
		}

		$url = add_query_arg(
			[
				'measurement_id' => rawurlencode( $measurement_id ),
				'api_secret'     => rawurlencode( $api_secret ),
			],
			self::ENDPOINT
		);

		$response = wp_remote_post(
			$url,
			[
				'headers'  => [ 'Content-Type' => 'application/json' ],
				'body'     => wp_json_encode(
					[
						'client_id' => $client_id,
						'events'    => array_slice( array_values( $events ), 0, 25 ),
					]
				),
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
			'ok'     => 200 === $code || 204 === $code,
			'status' => $code,
			'body'   => (string) wp_remote_retrieve_body( $response ),
		];
	}

	/**
	 * Resolve a stable client_id from the GA cookie or a hashed REMOTE_ADDR.
	 *
	 * @return string
	 */
	private static function resolve_client_id(): string {
		if ( ! empty( $_COOKIE['_ga'] ) ) {
			$ga    = sanitize_text_field( wp_unslash( (string) $_COOKIE['_ga'] ) );
			$parts = explode( '.', $ga );
			if ( count( $parts ) >= 4 ) {
				return $parts[2] . '.' . $parts[3];
			}
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
		return substr( md5( $ip ), 0, 16 );
	}
}

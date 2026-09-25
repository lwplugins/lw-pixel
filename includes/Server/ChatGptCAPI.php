<?php
/**
 * ChatGPT Ads Conversions API client.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\ChatGpt\EventMapper;
use LightweightPlugins\Pixel\Events\EventId;
use LightweightPlugins\Pixel\Options;

/**
 * POST https://bzr.openai.com/v1/events?pid=<PIXEL-ID> with
 * `Authorization: Bearer <API-KEY>` (OpenAI docs, checked 2026-09-26).
 *
 * The API key never leaves the server: it is not printed, logged or
 * returned. It can be defined as LW_PIXEL_CHATGPT_API_KEY in wp-config.php
 * instead of being stored in the database.
 */
final class ChatGptCAPI {

	private const ENDPOINT = 'https://bzr.openai.com/v1/events';

	/**
	 * Sent as `integration_source` (1–64 ASCII characters).
	 */
	public const INTEGRATION_SOURCE = 'lw-pixel';

	/**
	 * Max events per request (API limit).
	 */
	public const MAX_BATCH = 1000;

	/**
	 * The API key: the wp-config constant wins over the stored option.
	 *
	 * @return string
	 */
	public static function api_key(): string {
		if ( defined( 'LW_PIXEL_CHATGPT_API_KEY' ) && '' !== (string) constant( 'LW_PIXEL_CHATGPT_API_KEY' ) ) {
			return (string) constant( 'LW_PIXEL_CHATGPT_API_KEY' );
		}

		return (string) Options::get( 'chatgpt_api_key', '' );
	}

	/**
	 * Whether a pixel id and an API key are both available.
	 *
	 * @return bool
	 */
	public static function is_configured(): bool {
		return '' !== trim( (string) Options::get( 'chatgpt_pixel_id', '' ) ) && '' !== self::api_key();
	}

	/**
	 * Send a batch of complete event bodies.
	 *
	 * The whole batch fails when one event is invalid (API behaviour).
	 *
	 * @param array<int, array<string, mixed>> $events        Event bodies.
	 * @param bool                             $validate_only Validate without recording.
	 * @return array{ok: bool, status: int}
	 */
	public static function send( array $events, bool $validate_only = false ): array {
		if ( ! self::is_configured() || [] === $events ) {
			return [
				'ok'     => false,
				'status' => 0,
			];
		}

		$body = [
			'integration_source' => self::INTEGRATION_SOURCE,
			'events'             => array_slice( array_values( $events ), 0, self::MAX_BATCH ),
		];

		if ( $validate_only ) {
			$body = [ 'validate_only' => true ] + $body;
		}

		$response = wp_remote_post(
			add_query_arg( 'pid', rawurlencode( trim( (string) Options::get( 'chatgpt_pixel_id', '' ) ) ), self::ENDPOINT ),
			[
				'headers'  => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . self::api_key(),
				],
				'body'     => wp_json_encode( $body ),
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

		return [
			'ok'     => $code >= 200 && $code < 300,
			'status' => $code,
		];
	}

	/**
	 * Check the saved pixel id + API key with a `validate_only` request
	 * (OpenAI validates the event without recording it).
	 *
	 * @return array{ok: bool, status: int, message: string}
	 */
	public static function test_connection(): array {
		if ( ! self::is_configured() ) {
			return [
				'ok'      => false,
				'status'  => 0,
				'message' => __( 'Save a ChatGPT Ads pixel ID and Conversions API key first.', 'lw-pixel' ),
			];
		}

		$mapped = (array) EventMapper::map( 'PageView', [] );
		$result = self::send(
			[
				[
					'id'            => EventId::generate(),
					'type'          => $mapped['type'],
					'timestamp_ms'  => time() * 1000,
					'action_source' => 'web',
					'source_url'    => home_url( '/' ),
					'data'          => $mapped['data'],
				],
			],
			true
		);

		return $result + [ 'message' => self::describe( $result ) ];
	}

	/**
	 * Human-readable result of a test request (no response body: it is
	 * undocumented and may echo request data).
	 *
	 * @param array{ok: bool, status: int} $result Result.
	 * @return string
	 */
	private static function describe( array $result ): string {
		if ( $result['ok'] ) {
			return __( 'Connected. ChatGPT Ads accepted a test event (validation only, nothing was recorded).', 'lw-pixel' );
		}

		if ( 401 === $result['status'] || 403 === $result['status'] ) {
			return __( 'ChatGPT Ads rejected the API key. Check the key and that it belongs to this pixel.', 'lw-pixel' );
		}

		if ( 0 === $result['status'] ) {
			return __( 'Could not reach the ChatGPT Ads API from this server.', 'lw-pixel' );
		}

		return sprintf(
			/* translators: %d: HTTP status code. */
			__( 'ChatGPT Ads returned HTTP %d. Check the pixel ID and API key.', 'lw-pixel' ),
			$result['status']
		);
	}
}

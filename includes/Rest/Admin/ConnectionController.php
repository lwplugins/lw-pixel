<?php
/**
 * Connection test REST controller.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin;

use LightweightPlugins\Pixel\Server\ChatGptCAPI;
use LightweightPlugins\Pixel\Server\PinterestCAPI;
use WP_REST_Response;
use WP_REST_Server;

/**
 * POST lw-pixel/v1/admin/test/chatgpt: validates the saved ChatGPT Ads
 * pixel ID + Conversions API key with a validate-only request (OpenAI
 * records nothing), and POST lw-pixel/v1/admin/test/pinterest the saved
 * Pinterest ad account ID + conversion token with a `test=true` request
 * (Pinterest records nothing). Always answers 200 with { ok, status, message }; the
 * message never contains the key.
 */
final class ConnectionController {

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		Routes::add( '/admin/test/chatgpt', [ WP_REST_Server::CREATABLE => 'test_chatgpt' ], $this );
		Routes::add( '/admin/test/pinterest', [ WP_REST_Server::CREATABLE => 'test_pinterest' ], $this );
	}

	/**
	 * Run the ChatGPT Ads connection test.
	 *
	 * @return WP_REST_Response
	 */
	public function test_chatgpt(): WP_REST_Response {
		return self::response( ChatGptCAPI::test_connection() );
	}

	/**
	 * Run the Pinterest Conversions API connection test.
	 *
	 * @return WP_REST_Response
	 */
	public function test_pinterest(): WP_REST_Response {
		return self::response( PinterestCAPI::test_connection() );
	}

	/**
	 * The test result without anything else.
	 *
	 * @param array{ok: bool, status: int, message: string} $result Result.
	 * @return WP_REST_Response
	 */
	private static function response( array $result ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'ok'      => $result['ok'],
				'status'  => $result['status'],
				'message' => $result['message'],
			]
		);
	}
}

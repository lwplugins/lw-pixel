<?php
/**
 * Connection test REST controller.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin;

use LightweightPlugins\Pixel\Server\ChatGptCAPI;
use WP_REST_Response;
use WP_REST_Server;

/**
 * POST lw-pixel/v1/admin/test/chatgpt: validates the saved ChatGPT Ads
 * pixel ID + Conversions API key with a validate-only request (OpenAI
 * records nothing). Always answers 200 with { ok, status, message }; the
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
	}

	/**
	 * Run the ChatGPT Ads connection test.
	 *
	 * @return WP_REST_Response
	 */
	public function test_chatgpt(): WP_REST_Response {
		$result = ChatGptCAPI::test_connection();

		return new WP_REST_Response(
			[
				'ok'      => $result['ok'],
				'status'  => $result['status'],
				'message' => $result['message'],
			]
		);
	}
}

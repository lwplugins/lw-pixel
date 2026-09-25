<?php
/**
 * Tests for the Graph API version used by the Conversion API.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Server\FacebookCAPI;

/**
 * @covers \LightweightPlugins\Pixel\Server\FacebookCAPI
 */
final class FacebookCAPITest extends CapiTestCase {

	public function test_calls_a_supported_graph_api_version(): void {
		$url = '';
		Functions\when( 'wp_remote_post' )->alias(
			function ( $target ) use ( &$url ): array {
				$url = $target;
				return [];
			}
		);

		FacebookCAPI::send_server_event( 'Purchase', [], [ 'client_user_agent' => 'UA' ], 'https://shop.test/checkout/' );

		$this->assertSame( 'https://graph.facebook.com/v26.0/123/events', $url );
	}

	public function test_version_can_be_filtered(): void {
		Filters\expectApplied( 'lw_pixel_capi_api_version' )->andReturn( 'v27.0' );

		$this->assertSame( 'v27.0', FacebookCAPI::api_version() );
	}

	public function test_malformed_filtered_version_falls_back_to_the_default(): void {
		Filters\expectApplied( 'lw_pixel_capi_api_version' )->andReturn( '../me' );

		$this->assertSame( FacebookCAPI::API_VERSION, FacebookCAPI::api_version() );
	}
}

<?php
/**
 * Tests for the settings REST controller, end to end over stubbed storage.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Rest\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\DefaultOptions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Rest\Admin\SettingsController;
use WP_Error;
use WP_REST_Request;

/**
 * @covers \LightweightPlugins\Pixel\Rest\Admin\SettingsController
 * @covers \LightweightPlugins\Pixel\Rest\Admin\Settings\SettingsResponse
 * @covers \LightweightPlugins\Pixel\Rest\Admin\Settings\SettingsMeta
 */
final class SettingsControllerTest extends RestTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'is_admin' )->justReturn( true );
	}

	/**
	 * POST a body.
	 *
	 * @param array<string, mixed> $body Body.
	 * @return mixed
	 */
	private function post( array $body ) {
		$response = ( new SettingsController() )->save_settings( new WP_REST_Request( $body ) );

		return $response instanceof WP_Error ? $response : $response->get_data();
	}

	public function test_saving_one_switch_never_turns_the_others_off(): void {
		// Regression: the classic form posted no WooCommerce inputs while
		// WooCommerce was inactive, and every woo_* switch was saved as off.
		$this->post( [ 'fb_enabled' => true ] );

		$saved = $this->saved();
		$this->assertTrue( $saved['fb_enabled'] );
		$this->assertTrue( $saved['woo_purchase'] );
		$this->assertTrue( $saved['event_pageview'] );
	}

	public function test_an_invalid_field_saves_nothing(): void {
		$result = $this->post(
			[
				'fb_enabled'  => true,
				'fb_pixel_id' => 'not-a-number',
			]
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertArrayHasKey( 'fb_pixel_id', $result->get_error_data()['fields'] );
		$this->assertSame( [], $this->saved() );
	}

	public function test_a_too_large_body_is_refused(): void {
		$request = new WP_REST_Request( [], str_repeat( ' ', SettingsController::MAX_BYTES + 1 ) );

		$result = ( new SettingsController() )->save_settings( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 413, $result->get_error_data()['status'] );
	}

	public function test_secrets_are_never_returned(): void {
		$this->store[ Options::OPTION_NAME ] = array_merge( DefaultOptions::all(), [ 'fb_capi_token' => 'EAAB1234567890abcdefwxyz' ] );

		$data = ( new SettingsController() )->get_settings()->get_data();

		$this->assertNull( $data['options']['fb_capi_token'] );
		$this->assertSame( '…wxyz', $data['meta']['secrets']->fb_capi_token['hint'] );
		$this->assertStringNotContainsString( 'EAAB1234567890', (string) wp_json_encode_for_test( $data ) );
	}

	public function test_a_null_secret_keeps_the_stored_token(): void {
		$this->store[ Options::OPTION_NAME ] = array_merge( DefaultOptions::all(), [ 'fb_capi_token' => 'EAABtoken' ] );

		$this->post(
			[
				'fb_capi_token'   => null,
				'fb_capi_enabled' => true,
			]
		);

		$this->assertSame( 'EAABtoken', $this->saved()['fb_capi_token'] );
		$this->assertTrue( $this->saved()['fb_capi_enabled'] );
	}

	public function test_meta_lists_every_registered_pixel(): void {
		$data = ( new SettingsController() )->get_settings()->get_data();
		$ids  = array_column( $data['meta']['pixels'], 'id' );

		$this->assertContains( 'chatgpt', $ids );
		$this->assertSame( 'marketing', $data['meta']['pixels'][ array_search( 'fb', $ids, true ) ]['default_category'] );
	}
}

/**
 * JSON-encode a payload for a leak check.
 *
 * @param mixed $data Data.
 * @return string|false
 */
function wp_json_encode_for_test( mixed $data ) {
	return json_encode( $data, JSON_UNESCAPED_UNICODE );
}

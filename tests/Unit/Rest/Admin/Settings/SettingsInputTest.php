<?php
/**
 * Tests for the settings save validation.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Rest\Admin\Settings;

use LightweightPlugins\Pixel\DefaultOptions;
use LightweightPlugins\Pixel\Rest\Admin\Settings\SettingsInput;
use LightweightPlugins\Pixel\Tests\Unit\Rest\Admin\RestTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Rest\Admin\Settings\SettingsInput
 */
final class SettingsInputTest extends RestTestCase {

	/**
	 * Validate a body.
	 *
	 * @param array<string, mixed>  $body     Body.
	 * @param array<string, string> $locked   Locked keys.
	 * @param bool                  $can_code May write custom code.
	 * @return SettingsInput
	 */
	private function input( array $body, array $locked = [], bool $can_code = true ): SettingsInput {
		return new SettingsInput( $body, DefaultOptions::all(), [ 'fb', 'ga4', 'gtm', 'chatgpt' ], $locked, $can_code );
	}

	public function test_unknown_keys_are_reported(): void {
		$this->assertArrayHasKey( 'nope', $this->input( [ 'nope' => true ] )->errors() );
	}

	public function test_retired_keys_are_reported_as_unknown(): void {
		$this->assertArrayHasKey( 'gads_remarketing', $this->input( [ 'gads_remarketing' => true ] )->errors() );
	}

	public function test_a_null_secret_keeps_the_stored_one(): void {
		$this->assertSame( [ 'fb_enabled' => true ], $this->input( [ 'fb_capi_token' => null, 'fb_enabled' => true ] )->values() );
	}

	public function test_an_empty_secret_clears_it(): void {
		$this->assertSame( [ 'fb_capi_token' => '' ], $this->input( [ 'fb_capi_token' => '' ] )->values() );
	}

	public function test_a_secret_with_spaces_is_rejected(): void {
		$this->assertArrayHasKey( 'ga4_mp_api_secret', $this->input( [ 'ga4_mp_api_secret' => 'abc def' ] )->errors() );
	}

	public function test_a_secret_pinned_by_a_constant_cannot_be_changed(): void {
		$input = $this->input( [ 'chatgpt_api_key' => 'sk-new' ], [ 'chatgpt_api_key' => 'LW_PIXEL_CHATGPT_API_KEY' ] );

		$this->assertArrayHasKey( 'chatgpt_api_key', $input->errors() );
	}

	public function test_custom_code_needs_unfiltered_html(): void {
		$input = $this->input( [ 'head_code' => '<script></script>' ], [], false );

		$this->assertArrayHasKey( 'head_code', $input->errors() );
	}

	public function test_a_pixel_in_two_consent_categories_is_rejected(): void {
		$input = $this->input( [ 'consent_analytics_pixels' => [ 'ga4', 'fb' ] ] );

		$this->assertArrayHasKey( 'consent_analytics_pixels', $input->errors() );
	}

	public function test_moving_a_pixel_between_categories_in_one_save_is_valid(): void {
		$input = $this->input(
			[
				'consent_marketing_pixels' => [ 'chatgpt' ],
				'consent_analytics_pixels' => [ 'ga4', 'fb' ],
			]
		);

		$this->assertSame( [], $input->errors() );
	}

	public function test_no_values_are_returned_when_any_field_is_invalid(): void {
		$this->assertSame( [], $this->input( [ 'fb_enabled' => true, 'fb_pixel_id' => 'abc' ] )->values() );
	}
}

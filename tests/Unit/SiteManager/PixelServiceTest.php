<?php
/**
 * Tests for secret handling in the Site Manager abilities.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\SiteManager;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\SiteManager\PixelService;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\SiteManager\PixelService
 * @covers \LightweightPlugins\Pixel\Options
 */
final class PixelServiceTest extends MonkeyTestCase {

	/**
	 * Last value written with update_option().
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $saved = null;

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		$this->saved = null;

		Functions\when( 'get_option' )->justReturn(
			[
				'fb_capi_token'     => 'EAAB-secret-token',
				'ga4_mp_api_secret' => '',
			]
		);
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( '__' )->returnArg();
		Functions\when( 'update_option' )->alias(
			function ( $name, $value ): bool {
				unset( $name );
				$this->saved = $value;
				return true;
			}
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * The ability is reachable over MCP: its result lands in agent
	 * transcripts and logs, so the CAPI token must not be in it.
	 */
	public function test_get_options_masks_stored_secrets(): void {
		$options = PixelService::get_options()['options'];

		$this->assertSame( Options::SECRET_MASK, $options['fb_capi_token'] );
		$this->assertSame( '', $options['ga4_mp_api_secret'] );
	}

	public function test_sending_the_mask_back_keeps_the_stored_secret(): void {
		PixelService::set_options( [ 'options' => [ 'fb_capi_token' => Options::SECRET_MASK ] ] );

		$this->assertSame( 'EAAB-secret-token', $this->saved['fb_capi_token'] );
	}

	public function test_set_options_can_still_write_a_new_secret(): void {
		$result = PixelService::set_options( [ 'options' => [ 'ga4_mp_api_secret' => 'new-secret' ] ] );

		$this->assertSame( 'new-secret', $this->saved['ga4_mp_api_secret'] );
		$this->assertSame( [ 'ga4_mp_api_secret' ], $result['updated'] );
	}
}

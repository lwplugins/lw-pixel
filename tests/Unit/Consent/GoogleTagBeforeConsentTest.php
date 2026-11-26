<?php
/**
 * Tests for loading the Google tag before consent (Consent Mode v2, issue #28).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Consent;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Consent\GoogleTagBeforeConsent;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Consent\GoogleTagBeforeConsent
 */
final class GoogleTagBeforeConsentTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $options      Stored options.
	 * @param bool                 $consent_mode Whether Consent Mode v2 defaults are printed.
	 */
	private function given( array $options, bool $consent_mode ): void {
		Functions\when( 'get_option' )->justReturn(
			$options + [
				'ga4_enabled'        => true,
				'ga4_measurement_id' => 'G-ABC123',
			]
		);
		Filters\expectApplied( 'lw_pixel_consent_mode_active' )->andReturn( $consent_mode );
	}

	public function test_off_by_default_even_with_consent_mode(): void {
		$this->given( [], true );

		$this->assertFalse( GoogleTagBeforeConsent::is_active() );
	}

	public function test_needs_consent_mode_defaults(): void {
		$this->given( [ 'google_tag_before_consent' => true ], false );

		$this->assertFalse( GoogleTagBeforeConsent::is_active() );
	}

	public function test_active_with_the_option_and_consent_mode(): void {
		$this->given( [ 'google_tag_before_consent' => true ], true );

		$this->assertTrue( GoogleTagBeforeConsent::is_active() );
	}

	public function test_needs_a_configured_google_tag(): void {
		$this->given(
			[
				'google_tag_before_consent' => true,
				'ga4_enabled'               => false,
				'gads_enabled'              => true,
				'gads_conversion_id'        => '',
			],
			true
		);

		$this->assertFalse( GoogleTagBeforeConsent::is_active() );
	}

	public function test_google_ads_alone_is_enough(): void {
		$this->given(
			[
				'google_tag_before_consent' => true,
				'ga4_enabled'               => false,
				'gads_enabled'              => true,
				'gads_conversion_id'        => 'AW-123456',
			],
			true
		);

		$this->assertTrue( GoogleTagBeforeConsent::is_active() );
	}

	public function test_asks_lw_cookie_to_let_the_google_tag_host_through(): void {
		$this->given( [ 'google_tag_before_consent' => true ], true );

		$this->assertSame( [ 'youtube-nocookie.com', 'googletagmanager.com' ], GoogleTagBeforeConsent::exempt_host( [ 'youtube-nocookie.com' ] ) );
	}

	public function test_leaves_lw_cookie_alone_when_off(): void {
		$this->given( [], true );

		$this->assertSame( [], GoogleTagBeforeConsent::exempt_host( [] ) );
	}
}

<?php
/**
 * Tests for the Google Ads provider: conversions and consent-free base tag.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Pixels;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\GoogleAds;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Pixels\GoogleAds
 */
final class GoogleAdsTest extends MonkeyTestCase {

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
	 * @param array<string, mixed> $options Stored options.
	 */
	private function options( array $options ): void {
		Functions\when( 'get_option' )->justReturn(
			$options + [
				'gads_enabled'       => true,
				'gads_conversion_id' => 'AW-123456',
			]
		);
	}

	public function test_purchase_uses_the_purchase_label(): void {
		$this->options( [ 'gads_conversion_label' => 'buy1' ] );

		$mapped = ( new GoogleAds() )->map_event( 'Purchase', [ 'value' => 99.5, 'currency' => 'HUF', 'order_id' => '1001' ] );

		$this->assertSame(
			[
				'send_to'        => 'AW-123456/buy1',
				'value'          => 99.5,
				'currency'       => 'HUF',
				'transaction_id' => '1001',
			],
			$mapped
		);
	}

	public function test_a_listed_event_sends_its_own_conversion(): void {
		$this->options( [ 'gads_conversion_events' => "Lead=lead1\nContact=call1" ] );

		$this->assertSame( [ 'send_to' => 'AW-123456/call1' ], ( new GoogleAds() )->map_event( 'Contact', [ 'contact_method' => 'phone' ] ) );
	}

	public function test_a_custom_event_with_a_value_sends_the_value(): void {
		$this->options( [ 'gads_conversion_events' => 'QuoteRequest=quote1' ] );

		$mapped = ( new GoogleAds() )->map_event( 'QuoteRequest', [ 'value' => '1500', 'currency' => 'HUF' ] );

		$this->assertSame(
			[
				'send_to'  => 'AW-123456/quote1',
				'value'    => 1500.0,
				'currency' => 'HUF',
			],
			$mapped
		);
	}

	public function test_an_unlisted_event_sends_nothing(): void {
		$this->options( [ 'gads_conversion_events' => 'Lead=lead1' ] );

		$this->assertNull( ( new GoogleAds() )->map_event( 'PageView', [] ) );
	}

	public function test_purchase_can_come_from_the_list(): void {
		$this->options( [ 'gads_conversion_events' => 'Purchase=buy2' ] );

		$this->assertSame( 'AW-123456/buy2', ( new GoogleAds() )->map_event( 'Purchase', [ 'order_id' => '7' ] )['send_to'] ?? null );
	}

	public function test_base_tag_loads_before_consent_only_when_enabled(): void {
		$this->options( [ 'google_tag_before_consent' => true ] );
		Filters\expectApplied( 'lw_pixel_consent_mode_active' )->andReturn( true );

		$this->assertTrue( ( new GoogleAds() )->get_frontend_config()['baseWithoutConsent'] );
	}
}

<?php
/**
 * Tests for the consent manager (cache-safe client gating — issue #3).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Consent;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Consent\Manager;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Consent\Manager
 */
final class ManagerTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_client_gating_on_when_lw_cookie_hook_is_present(): void {
		Functions\when( 'has_filter' )->justReturn( 10 );

		$this->assertTrue( ( new Manager() )->has_client_gating() );
	}

	public function test_client_gating_off_without_a_consent_plugin(): void {
		Functions\when( 'has_filter' )->justReturn( false );

		$this->assertFalse( ( new Manager() )->has_client_gating() );
	}

	/**
	 * has_filter() returns 0 for a filter hooked at priority 0 — that is a real
	 * hook, not "absent", so a naive (bool) cast would be a bug.
	 */
	public function test_client_gating_on_at_priority_zero(): void {
		Functions\when( 'has_filter' )->justReturn( 0 );

		$this->assertTrue( ( new Manager() )->has_client_gating() );
	}

	public function test_pixel_categories_map_from_options(): void {
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = array() ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'get_option' )->justReturn(
			array(
				'consent_marketing_pixels'    => array( 'fb', 'tiktok' ),
				'consent_analytics_pixels'    => array( 'ga4' ),
				'consent_unclassified_pixels' => array( 'gtm' ),
			)
		);

		$map = ( new Manager() )->get_pixel_categories();

		$this->assertSame( 'marketing', $map['fb'] );
		$this->assertSame( 'marketing', $map['tiktok'] );
		$this->assertSame( 'analytics', $map['ga4'] );
		$this->assertSame( 'functional', $map['gtm'] );
	}
}

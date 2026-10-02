<?php
/**
 * Tests for the frontend payload builder.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Events;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\Events\PayloadBuilder;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\PixelManager;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Events\PayloadBuilder
 */
final class PayloadBuilderTest extends MonkeyTestCase {

	/**
	 * A product page view.
	 *
	 * @var array<int, array{name: string, params: array<string, mixed>}>
	 */
	private const QUEUE = [
		[
			'name'   => 'ViewContent',
			'params' => [
				'content_id'   => '42',
				'content_name' => 'Cable',
				'content_type' => 'product',
				'quantity'     => 1,
				'price'        => 100.0,
				'currency'     => 'HUF',
			],
		],
	];

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'get_option' )->justReturn(
			[
				'barion_enabled'     => true,
				'barion_pixel_id'    => 'BP-krFBuKtgmG-B4',
				'ga4_enabled'        => true,
				'ga4_measurement_id' => 'G-TEST1234',
			]
		);
		// The generic consent filter: analytics granted, marketing not.
		Functions\when( 'apply_filters' )->alias(
			static fn ( string $hook, $value, ...$args ) => 'lw_pixel_is_category_allowed' === $hook
				? 'marketing' !== $args[0]
				: $value
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Pixel ids the product view is mapped for.
	 *
	 * @return array<int, string>
	 */
	private static function mapped_pixels(): array {
		$events = ( new PayloadBuilder( new PixelManager(), new ConsentManager() ) )->build_events( self::QUEUE );

		return array_keys( $events[0]['mapped'] ?? [] );
	}

	public function test_server_side_gating_maps_no_events_for_a_consent_free_base(): void {
		Functions\when( 'has_filter' )->justReturn( false );

		$this->assertSame( [ 'ga4' ], self::mapped_pixels() );
	}

	public function test_client_side_gating_maps_events_for_every_configured_pixel(): void {
		Functions\when( 'has_filter' )->justReturn( 10 );

		$this->assertSame( [ 'ga4', 'barion' ], self::mapped_pixels() );
	}
}

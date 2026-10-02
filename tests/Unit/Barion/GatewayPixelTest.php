<?php
/**
 * Tests for the Barion payment gateway pixel switch.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Barion;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Barion\GatewayPixel;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\PixelManager;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Barion\GatewayPixel
 */
final class GatewayPixelTest extends MonkeyTestCase {

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
	 * @dataProvider provide_settings
	 *
	 * @param array<string, mixed> $options  Stored options.
	 * @param bool                 $expected Whether the gateway pixel is switched off.
	 */
	public function test_gateway_pixel_is_off_while_the_barion_pixel_is_configured( array $options, bool $expected ): void {
		Functions\when( 'get_option' )->justReturn( $options );

		$this->assertSame( $expected, GatewayPixel::disable( false, new PixelManager() ) );
	}

	/**
	 * @return array<string, array{array<string, mixed>, bool}>
	 */
	public static function provide_settings(): array {
		return [
			'configured'      => [
				[
					'barion_enabled'  => true,
					'barion_pixel_id' => 'BP-krFBuKtgmG-B4',
				],
				true,
			],
			'enabled, no ID'  => [ [ 'barion_enabled' => true ], false ],
			'ID, not enabled' => [ [ 'barion_pixel_id' => 'BP-krFBuKtgmG-B4' ], false ],
		];
	}

	public function test_an_earlier_switch_off_is_kept(): void {
		Functions\when( 'get_option' )->justReturn( [] );

		$this->assertTrue( GatewayPixel::disable( true, new PixelManager() ) );
	}
}

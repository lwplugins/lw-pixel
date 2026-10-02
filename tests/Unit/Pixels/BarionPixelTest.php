<?php
/**
 * Tests for the Barion Pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Pixels;

use Brain\Monkey\Functions;
use Mockery;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\BarionPixel;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Pixels\BarionPixel
 */
final class BarionPixelTest extends MonkeyTestCase {

	/**
	 * Purchase params of order 1001.
	 *
	 * @var array<string, mixed>
	 */
	private const PURCHASE = [
		'contents' => [
			[
				'content_id'   => '42',
				'content_name' => 'Cable',
				'content_type' => 'product',
				'quantity'     => 1,
				'price'        => 100.0,
				'currency'     => 'HUF',
			],
		],
		'value'    => 100,
		'currency' => 'HUF',
		'order_id' => '1001',
	];

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'apply_filters' )->returnArg( 2 );
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
				'barion_enabled'  => true,
				'barion_pixel_id' => 'BP-krFBuKtgmG-B4',
			]
		);
	}

	/**
	 * An order with the given billing email.
	 *
	 * @param string $email Billing email.
	 * @return \WC_Order
	 */
	private static function order( string $email ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_billing_email' )->andReturn( $email );

		return $order;
	}

	public function test_base_loads_without_consent_by_default(): void {
		$this->options( [] );

		$pixel = new BarionPixel();

		$this->assertTrue( $pixel->is_configured() );
		$this->assertSame(
			[
				'pixelId'            => 'BP-krFBuKtgmG-B4',
				'baseWithoutConsent' => true,
			],
			$pixel->get_frontend_config()
		);
	}

	public function test_base_waits_for_consent_when_switched_off(): void {
		$this->options( [ 'barion_base_without_consent' => false ] );

		$this->assertFalse( ( new BarionPixel() )->loads_base_without_consent() );
	}

	public function test_purchase_carries_the_sha1_of_the_lowercased_billing_email(): void {
		$this->options( [] );
		Functions\when( 'wc_get_order' )->justReturn( self::order( ' Vevo@Example.HU ' ) );

		$mapped = ( new BarionPixel() )->map_event( 'Purchase', self::PURCHASE );

		$this->assertSame( sha1( 'vevo@example.hu' ), $mapped['email'] ?? null );
	}

	public function test_purchase_sends_no_email_when_switched_off(): void {
		$this->options( [ 'barion_encrypted_email' => false ] );
		Functions\when( 'wc_get_order' )->justReturn( self::order( 'vevo@example.hu' ) );

		$mapped = ( new BarionPixel() )->map_event( 'Purchase', self::PURCHASE );

		$this->assertArrayNotHasKey( 'email', (array) $mapped );
	}
}

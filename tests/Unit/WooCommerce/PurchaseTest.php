<?php
/**
 * Tests for the browser Purchase event's tracked flag.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\Pixel\WooCommerce\Events\Purchase;
use Mockery;

/**
 * @covers \LightweightPlugins\Pixel\WooCommerce\Events\Purchase
 */
final class PurchaseTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Deciding to fire must not mark the order: on classic themes the event
	 * used to be lost after wp_head while the order was already flagged.
	 */
	public function test_should_fire_does_not_mark_the_order_tracked(): void {
		$order = $this->order( '' );
		$order->shouldReceive( 'update_meta_data' )->never();

		$this->assertTrue( ( new Purchase( 42 ) )->should_fire() );
	}

	public function test_mark_tracked_writes_the_flag_through_the_order_crud_api(): void {
		$order = $this->order( '' );
		$order->shouldReceive( 'update_meta_data' )->once()->with( Purchase::TRACKED_META, '1' );
		$order->shouldReceive( 'save' )->once();

		( new Purchase( 42 ) )->mark_tracked();
	}

	public function test_already_tracked_order_does_not_fire(): void {
		$this->order( '1' );

		$this->assertFalse( ( new Purchase( 42 ) )->should_fire() );
	}

	/**
	 * WooCommerce shows the thank-you page for a declined payment too.
	 *
	 * @dataProvider provide_statuses
	 */
	public function test_fires_only_for_orders_that_are_a_purchase( string $status, bool $fires ): void {
		$this->order( '', $status );

		$this->assertSame( $fires, ( new Purchase( 42 ) )->should_fire() );
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function provide_statuses(): array {
		return [
			'failed'     => [ 'failed', false ],
			'cancelled'  => [ 'cancelled', false ],
			'pending'    => [ 'pending', true ],
			'on-hold'    => [ 'on-hold', true ],
			'processing' => [ 'processing', true ],
		];
	}

	/**
	 * Register a WC order mock with the given tracked-flag value.
	 *
	 * @param string $tracked Stored flag.
	 * @param string $status  Order status.
	 * @return \Mockery\MockInterface
	 */
	private function order( string $tracked, string $status = 'processing' ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'has_status' )->andReturnUsing(
			static fn ( $statuses ): bool => in_array( $status, (array) $statuses, true )
		);
		$order->shouldReceive( 'get_meta' )->with( Purchase::TRACKED_META, true )->andReturn( $tracked );
		$order->shouldReceive( 'get_items' )->andReturn( [] );
		$order->shouldReceive( 'get_total' )->andReturn( 10.0 );
		$order->shouldReceive( 'get_currency' )->andReturn( 'HUF' );
		$order->shouldReceive( 'get_id' )->andReturn( 42 );
		Functions\when( 'wc_get_order' )->justReturn( $order );

		return $order;
	}
}

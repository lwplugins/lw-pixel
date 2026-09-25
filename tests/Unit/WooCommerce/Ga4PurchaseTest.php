<?php
/**
 * Tests for the GA4 purchase shape shared by gtag and the Measurement Protocol.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\GoogleAnalytics4;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\Pixel\WooCommerce\Ga4Purchase;
use Mockery;

/**
 * @covers \LightweightPlugins\Pixel\WooCommerce\Ga4Purchase
 * @covers \LightweightPlugins\Pixel\Pixels\GoogleAnalytics4::map_event
 */
final class Ga4PurchaseTest extends MonkeyTestCase {

	private const PARAMS = [
		'value'    => 150.0,
		'currency' => 'HUF',
		'order_id' => '42',
		'contents' => [ [ 'content_id' => '7', 'content_name' => 'Hat', 'quantity' => 2, 'price' => 63.5 ] ],
	];

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
	 * Value = sum of discounted net unit price * quantity: no tax, no shipping.
	 */
	public function test_value_and_items_come_from_net_line_totals(): void {
		$this->order_with_item( 50.0 );

		$params = Ga4Purchase::params( self::PARAMS );

		$this->assertSame( '42', $params['transaction_id'] );
		$this->assertSame( 'HUF', $params['currency'] );
		$this->assertSame( 100.0, $params['value'] );
		$this->assertSame(
			[
				[
					'item_id'   => '7',
					'item_name' => 'Hat',
					'price'     => 50.0,
					'quantity'  => 2,
				],
			],
			$params['items']
		);
	}

	public function test_medical_mode_leaves_item_names_out(): void {
		Options::clear_cache();
		Functions\when( 'get_option' )->justReturn( [ 'compliance_medical' => true ] );
		$this->order_with_item( 50.0 );

		$this->assertArrayNotHasKey( 'item_name', Ga4Purchase::params( self::PARAMS )['items'][0] );
	}

	public function test_browser_purchase_uses_the_same_shape_and_is_exact(): void {
		$this->order_with_item( 50.0 );

		$mapped = ( new GoogleAnalytics4() )->map_event( 'Purchase', self::PARAMS );

		$this->assertSame( 'purchase', $mapped['name'] );
		$this->assertTrue( $mapped['exact'] );
		$this->assertSame( '42', $mapped['params']['transaction_id'] );
		$this->assertSame( 100.0, $mapped['params']['value'] );
		$this->assertArrayNotHasKey( 'contents', $mapped['params'] );
	}

	public function test_a_custom_purchase_without_an_order_passes_through(): void {
		$mapped = ( new GoogleAnalytics4() )->map_event( 'Purchase', [ 'value' => 5 ] );

		$this->assertSame( [ 'value' => 5 ], $mapped['params'] );
		$this->assertArrayNotHasKey( 'exact', $mapped );
	}

	/**
	 * An order with one product line (qty 2) at the given net unit price.
	 *
	 * @param float $net_unit Discounted unit price excluding tax.
	 * @return void
	 */
	private function order_with_item( float $net_unit ): void {
		$product = Mockery::mock( 'WC_Product' );
		$product->shouldReceive( 'get_id' )->andReturn( 7 );
		$product->shouldReceive( 'get_sku' )->andReturn( '' );
		$product->shouldReceive( 'get_name' )->andReturn( 'Hat' );

		$item = Mockery::mock( 'WC_Order_Item_Product' );
		$item->shouldReceive( 'get_product' )->andReturn( $product );
		$item->shouldReceive( 'get_quantity' )->andReturn( 2 );

		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_items' )->andReturn( [ $item ] );
		$order->shouldReceive( 'get_item_total' )->with( $item, false, false )->andReturn( $net_unit );

		Functions\when( 'wc_get_order' )->justReturn( $order );
	}
}

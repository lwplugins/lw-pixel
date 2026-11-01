<?php
/**
 * Tests for the GA4 ecommerce dataLayer shape of the GTM provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\GoogleAnalytics4;
use LightweightPlugins\Pixel\Pixels\GoogleTagManager;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\Pixel\WooCommerce\Ga4Ecommerce;
use Mockery;

/**
 * @covers \LightweightPlugins\Pixel\WooCommerce\Ga4Ecommerce
 * @covers \LightweightPlugins\Pixel\Pixels\GoogleTagManager::map_event
 */
final class Ga4EcommerceTest extends MonkeyTestCase {

	private const PRODUCT = [
		'content_id'   => '7',
		'content_name' => 'Hat',
		'content_type' => 'product',
		'quantity'     => 2,
		'price'        => 12.5,
		'currency'     => 'HUF',
	];

	private const PURCHASE = [
		'value'    => 150.0,
		'currency' => 'HUF',
		'order_id' => '42',
		'contents' => [ [ 'content_id' => '7', 'content_name' => 'Hat', 'quantity' => 2, 'price' => 63.5 ] ],
	];

	protected function setUp(): void {
		parent::setUp();
		$this->options( [] );
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_purchase_uses_the_order_number_as_transaction_id(): void {
		$this->order( 'R-1001' );

		$out = Ga4Ecommerce::for_event( 'Purchase', self::PURCHASE );

		$this->assertSame( 'purchase', $out['event'] );
		$this->assertSame( 'R-1001', $out['ecommerce']['transaction_id'] );
		$this->assertSame( 'HUF', $out['ecommerce']['currency'] );
		$this->assertSame( 100.0, $out['ecommerce']['value'] );
		$this->assertSame( [ [ 'item_id' => '7', 'item_name' => 'Hat', 'price' => 50.0, 'quantity' => 2 ] ], $out['ecommerce']['items'] );
	}

	public function test_gross_prices_put_tax_on_the_items_but_keep_the_value_net(): void {
		$this->options( [ 'gtm_ga4_gross_prices' => true ] );
		$this->order( 'R-1001' );

		$out = Ga4Ecommerce::for_event( 'Purchase', self::PURCHASE );

		$this->assertSame( 63.5, $out['ecommerce']['items'][0]['price'] );
		$this->assertSame( 100.0, $out['ecommerce']['value'] );
	}

	public function test_the_ga4_tag_purchase_keeps_net_prices_with_the_gtm_option_on(): void {
		$this->options( [ 'gtm_ga4_gross_prices' => true ] );
		$this->order( 'R-1001' );

		$mapped = ( new GoogleAnalytics4() )->map_event( 'Purchase', self::PURCHASE );

		$this->assertSame( 50.0, $mapped['params']['items'][0]['price'] );
	}

	public function test_a_purchase_without_an_order_id_is_not_ecommerce(): void {
		$this->assertNull( Ga4Ecommerce::for_event( 'Purchase', [ 'value' => 5 ] ) );
	}

	/**
	 * @dataProvider provide_product_events
	 */
	public function test_product_events_carry_one_item_and_its_value( string $event, string $ga4 ): void {
		$out = Ga4Ecommerce::for_event( $event, self::PRODUCT );

		$this->assertSame(
			[
				'event'     => $ga4,
				'ecommerce' => [
					'currency' => 'HUF',
					'value'    => 25.0,
					'items'    => [ [ 'item_id' => '7', 'item_name' => 'Hat', 'price' => 12.5, 'quantity' => 2 ] ],
				],
			],
			$out
		);
	}

	public static function provide_product_events(): array {
		return [
			'view_item'   => [ 'ViewContent', 'view_item' ],
			'add_to_cart' => [ 'AddToCart', 'add_to_cart' ],
		];
	}

	public function test_begin_checkout_carries_every_cart_line(): void {
		$cart = [
			'contents' => [ self::PRODUCT, [ 'content_id' => '9', 'content_name' => 'Cap', 'quantity' => 1, 'price' => 5.0 ] ],
			'currency' => 'HUF',
		];

		$out = Ga4Ecommerce::for_event( 'InitiateCheckout', $cart );

		$this->assertSame( 'begin_checkout', $out['event'] );
		$this->assertSame( 30.0, $out['ecommerce']['value'] );
		$this->assertCount( 2, $out['ecommerce']['items'] );
	}

	/**
	 * @dataProvider provide_non_ecommerce
	 */
	public function test_other_events_are_not_ecommerce( string $event, array $params ): void {
		$this->assertNull( Ga4Ecommerce::for_event( $event, $params ) );
	}

	public static function provide_non_ecommerce(): array {
		return [
			'post view'  => [ 'ViewContent', [ 'content_id' => '3', 'content_type' => 'post' ] ],
			'empty cart' => [ 'InitiateCheckout', [ 'contents' => [], 'currency' => 'HUF' ] ],
			'lead'       => [ 'Lead', self::PRODUCT ],
		];
	}

	public function test_medical_mode_leaves_item_names_out(): void {
		$this->options( [ 'compliance_medical' => true ] );

		$out = Ga4Ecommerce::for_event( 'AddToCart', self::PRODUCT );

		$this->assertArrayNotHasKey( 'item_name', $out['ecommerce']['items'][0] );
	}

	public function test_gtm_pushes_the_flat_event_while_the_option_is_off(): void {
		$mapped = ( new GoogleTagManager() )->map_event( 'AddToCart', self::PRODUCT );

		$this->assertSame( [ 'event' => 'AddToCart', 'params' => self::PRODUCT ], $mapped );
	}

	public function test_gtm_pushes_the_ga4_shape_when_the_option_is_on(): void {
		$this->options( [ 'gtm_ga4_ecommerce' => true ] );

		$mapped = ( new GoogleTagManager() )->map_event( 'AddToCart', self::PRODUCT );

		$this->assertSame( 'add_to_cart', $mapped['event'] );
		$this->assertArrayHasKey( 'ecommerce', $mapped );
	}

	public function test_gtm_keeps_non_ecommerce_events_flat_when_the_option_is_on(): void {
		$this->options( [ 'gtm_ga4_ecommerce' => true ] );

		$mapped = ( new GoogleTagManager() )->map_event( 'Lead', [ 'form' => 'x' ] );

		$this->assertSame( [ 'event' => 'Lead', 'params' => [ 'form' => 'x' ] ], $mapped );
	}

	/**
	 * Stored plugin options.
	 *
	 * @param array<string, mixed> $stored Stored options.
	 * @return void
	 */
	private function options( array $stored ): void {
		Options::clear_cache();
		Functions\when( 'get_option' )->justReturn( $stored );
	}

	/**
	 * An order with one product line (qty 2) at a net unit price of 50 (63.5 with tax).
	 *
	 * @param string $number Order number shown to the customer.
	 * @return void
	 */
	private function order( string $number ): void {
		$product = Mockery::mock( 'WC_Product' );
		$product->shouldReceive( 'get_id' )->andReturn( 7 );
		$product->shouldReceive( 'get_sku' )->andReturn( '' );
		$product->shouldReceive( 'get_name' )->andReturn( 'Hat' );

		$item = Mockery::mock( 'WC_Order_Item_Product' );
		$item->shouldReceive( 'get_product' )->andReturn( $product );
		$item->shouldReceive( 'get_quantity' )->andReturn( 2 );

		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_items' )->andReturn( [ $item ] );
		$order->shouldReceive( 'get_item_total' )->with( $item, false, false )->andReturn( 50.0 );
		$order->shouldReceive( 'get_item_total' )->with( $item, true, false )->andReturn( 63.5 );
		$order->shouldReceive( 'get_order_number' )->andReturn( $number );

		Functions\when( 'wc_get_order' )->justReturn( $order );
	}
}

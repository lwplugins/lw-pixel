<?php
/**
 * Tests for the Barion Pixel event mapper.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Barion;

use LightweightPlugins\Pixel\Barion\EventMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\Barion\EventMapper
 */
final class EventMapperTest extends TestCase {

	/**
	 * Generic params of one product (ProductData::for_product shape).
	 *
	 * @param int $quantity Quantity.
	 * @return array<string, mixed>
	 */
	private static function product( int $quantity = 1 ): array {
		return [
			'content_id'   => '42',
			'content_name' => 'Type 2 cable',
			'content_type' => 'product',
			'quantity'     => $quantity,
			'price'        => 12500.0,
			'currency'     => 'huf',
		];
	}

	public function test_product_view_is_a_content_view_without_total_item_price(): void {
		$mapped = EventMapper::map( 'ViewContent', self::product() );

		$this->assertSame(
			[
				'name' => 'contentView',
				'data' => [
					'contentType' => 'Product',
					'currency'    => 'HUF',
					'id'          => '42',
					'name'        => 'Type 2 cable',
					'quantity'    => 1,
					'unit'        => 'pcs',
					'unitPrice'   => 12500.0,
				],
			],
			$mapped
		);
	}

	/**
	 * @dataProvider provide_page_types
	 */
	public function test_non_product_view_is_a_page_or_article( string $post_type, string $expected ): void {
		$mapped = EventMapper::map(
			'ViewContent',
			[
				'content_id'   => '7',
				'content_name' => 'About',
				'content_type' => $post_type,
			]
		);

		$this->assertSame(
			[
				'contentType' => $expected,
				'id'          => '7',
				'name'        => 'About',
			],
			$mapped['data'] ?? null
		);
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function provide_page_types(): array {
		return [
			'post is an article' => [ 'post', 'Article' ],
			'page is a page'     => [ 'page', 'Page' ],
			'other CPT is page'  => [ 'event', 'Page' ],
		];
	}

	public function test_add_to_cart_carries_quantity_total_and_step(): void {
		$mapped = EventMapper::map( 'AddToCart', self::product( 3 ) );

		$this->assertSame( 'addToCart', $mapped['name'] ?? null );
		$this->assertSame( 3, $mapped['data']['quantity'] );
		$this->assertSame( 37500.0, $mapped['data']['totalItemPrice'] );
		$this->assertSame( 1, $mapped['data']['step'] );
	}

	public function test_purchase_has_contents_revenue_and_order_number(): void {
		$mapped = EventMapper::map(
			'Purchase',
			[
				'contents' => [ self::product( 2 ) ],
				'value'    => 26490,
				'currency' => 'HUF',
				'order_id' => '1001',
			]
		);

		$this->assertSame( 'purchase', $mapped['name'] ?? null );
		$this->assertSame( [ 'contents', 'currency', 'revenue', 'step', 'orderNumber' ], array_keys( $mapped['data'] ) );
		$this->assertSame( 26490.0, $mapped['data']['revenue'] );
		$this->assertSame( '1001', $mapped['data']['orderNumber'] );
		$this->assertSame( 25000.0, $mapped['data']['contents'][0]['totalItemPrice'] );
	}

	public function test_checkout_with_an_incomplete_line_is_skipped(): void {
		$line = self::product();
		unset( $line['price'] );

		$mapped = EventMapper::map(
			'InitiateCheckout',
			[
				'contents' => [ self::product(), $line ],
				'value'    => 100,
				'currency' => 'HUF',
			]
		);

		$this->assertNull( $mapped );
	}

	public function test_missing_name_falls_back_to_the_id(): void {
		$params = self::product();
		unset( $params['content_name'] );

		$mapped = EventMapper::map( 'ViewContent', $params );

		$this->assertSame( '42', $mapped['data']['name'] ?? null );
	}

	public function test_category_view_is_a_category_selection(): void {
		$mapped = EventMapper::map(
			'ViewCategory',
			[
				'content_category' => 'Cables',
				'category_id'      => '15',
			]
		);

		$this->assertSame(
			[
				'name' => 'categorySelection',
				'data' => [
					'id'   => '15',
					'name' => 'Cables',
				],
			],
			$mapped
		);
	}

	public function test_search_without_a_string_is_skipped(): void {
		$this->assertNull( EventMapper::map( 'Search', [ 'search_string' => '' ] ) );
	}

	/**
	 * @dataProvider provide_unmapped_events
	 */
	public function test_events_without_a_barion_equivalent_are_skipped( string $name ): void {
		$this->assertNull( EventMapper::map( $name, self::product() ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provide_unmapped_events(): array {
		return [
			'page view (sent by bp.js)'      => [ 'PageView' ],
			'payment info (no method known)' => [ 'AddPaymentInfo' ],
			'custom event'                   => [ 'Booked Demo' ],
		];
	}
}

<?php
/**
 * Tests for the generic → ChatGPT Ads event mapping.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\ChatGpt;

use LightweightPlugins\Pixel\ChatGpt\EventMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\ChatGpt\EventMapper
 */
final class EventMapperTest extends TestCase {

	public function test_purchase_maps_to_order_created_in_minor_units(): void {
		$mapped = EventMapper::map(
			'Purchase',
			[
				'value'    => 129.99,
				'currency' => 'USD',
				'order_id' => '42',
				'contents' => [
					[
						'content_id'   => 'SKU-1',
						'content_name' => 'Shirt',
						'content_type' => 'product',
						'quantity'     => 2,
						'price'        => 50.0,
						'currency'     => 'USD',
					],
				],
			]
		);

		$this->assertSame( 'order_created', $mapped['type'] );
		$this->assertSame( 'contents', $mapped['data']['type'] );
		$this->assertSame( 12999, $mapped['data']['amount'] );
		$this->assertSame( 'USD', $mapped['data']['currency'] );
		$this->assertSame(
			[
				'id'           => 'SKU-1',
				'name'         => 'Shirt',
				'content_type' => 'product',
				'quantity'     => 2,
				'amount'       => 5000,
				'currency'     => 'USD',
			],
			$mapped['data']['contents'][0]
		);
	}

	public function test_single_product_add_to_cart_uses_price_times_quantity(): void {
		$mapped = EventMapper::map(
			'AddToCart',
			[
				'content_id'   => '7',
				'content_name' => 'Mug',
				'content_type' => 'product',
				'quantity'     => 3,
				'price'        => 1000.0,
				'currency'     => 'HUF',
			]
		);

		$this->assertSame( 'items_added', $mapped['type'] );
		$this->assertSame( 300000, $mapped['data']['amount'] );
		$this->assertSame( '7', $mapped['data']['contents'][0]['id'] );
	}

	public function test_page_view_has_no_contents(): void {
		$mapped = EventMapper::map( 'PageView', [ 'page_title' => 'Home' ] );

		$this->assertSame( [ 'type' => 'contents' ], $mapped['data'] );
		$this->assertSame( 'page_viewed', $mapped['type'] );
	}

	public function test_post_view_is_a_page_content(): void {
		$mapped = EventMapper::map(
			'ViewContent',
			[
				'content_id'   => '5',
				'content_name' => 'Hello',
				'content_type' => 'post',
			]
		);

		$this->assertSame( 'contents_viewed', $mapped['type'] );
		$this->assertSame( 'page', $mapped['data']['contents'][0]['content_type'] );
	}

	public function test_lead_without_value_is_a_plain_customer_action(): void {
		$mapped = EventMapper::map( 'Lead', [ 'currency' => 'USD', 'value' => 0 ] );

		$this->assertSame( 'lead_created', $mapped['type'] );
		$this->assertSame( [ 'type' => 'customer_action' ], $mapped['data'] );
	}

	public function test_registration(): void {
		$this->assertSame( 'registration_completed', EventMapper::map( 'CompleteRegistration', [] )['type'] );
	}

	public function test_user_custom_event_maps_to_custom(): void {
		$mapped = EventMapper::map( 'Newsletter Signup', [ 'value' => '5', 'currency' => 'EUR' ] );

		$this->assertSame( 'custom', $mapped['type'] );
		$this->assertSame( 'newsletter_signup', $mapped['custom_event_name'] );
		$this->assertSame( [ 'type' => 'custom', 'amount' => 500, 'currency' => 'EUR' ], $mapped['data'] );
	}

	public function test_builtin_events_without_equivalent_are_skipped(): void {
		$this->assertNull( EventMapper::map( 'Scroll', [] ) );
		$this->assertNull( EventMapper::map( 'Search', [ 'search_string' => 'x' ] ) );
		$this->assertNull( EventMapper::map( '!!!', [] ) );
	}
}

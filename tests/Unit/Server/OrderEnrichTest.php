<?php
/**
 * Tests for the server-side Purchase sent on order status changes.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Server\CheckoutContext;
use LightweightPlugins\Pixel\Server\OrderEnrich;

/**
 * @covers \LightweightPlugins\Pixel\Server\OrderEnrich
 * @covers \LightweightPlugins\Pixel\Server\FacebookCAPI
 */
final class OrderEnrichTest extends CapiTestCase {

	private const CUSTOMER_CONTEXT = [
		'consent' => CheckoutContext::GRANTED,
		'ip'  => '203.0.113.7',
		'ua'  => 'CustomerBrowser/1.0',
		'fbp' => 'fb.1.111.customer',
		'fbc' => 'fb.1.222.customerclick',
		'url' => 'https://shop.test/checkout/',
	];

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'update_post_meta' )->justReturn( true );
	}

	/**
	 * An admin marks a BACS order processing in wp-admin: the payload must
	 * carry the customer's checkout context, not the admin's request data.
	 */
	public function test_sends_the_checkout_context_not_the_acting_admins_request(): void {
		$this->use_options( [ 'fb_send_external_id' => true, 'fb_advanced_matching' => true ] );

		$_SERVER['REMOTE_ADDR']     = '198.51.100.1';
		$_SERVER['HTTP_USER_AGENT'] = 'AdminBrowser/9.9';
		$_SERVER['REQUEST_URI']     = '/wp-admin/post.php?post=42&action=edit';
		$_COOKIE['_fbp']            = 'fb.1.999.admin';

		Functions\expect( 'get_current_user_id' )->never();
		Functions\expect( 'wp_get_current_user' )->never();
		Functions\when( 'wc_get_order' )->justReturn( $this->order( [ CheckoutContext::META_KEY => self::CUSTOMER_CONTEXT ] ) );

		OrderEnrich::enrich( 42 );

		$this->assertCount( 1, $this->sent );
		$event = $this->sent[0]['data'][0];

		$this->assertSame( '203.0.113.7', $event['user_data']['client_ip_address'] );
		$this->assertSame( 'CustomerBrowser/1.0', $event['user_data']['client_user_agent'] );
		$this->assertSame( 'fb.1.111.customer', $event['user_data']['fbp'] );
		$this->assertSame( 'fb.1.222.customerclick', $event['user_data']['fbc'] );
		$this->assertSame( 'https://shop.test/checkout/', $event['event_source_url'] );
		$this->assertArrayNotHasKey( 'external_id', $event['user_data'] );
		$this->assertSame( hash( 'sha256', 'buyer@example.com' ), $event['user_data']['em'] );
	}

	/**
	 * GDPR: a visitor who refused marketing cookies at checkout must not
	 * have their order data sent to Meta from the server.
	 */
	public function test_skips_orders_whose_customer_refused_consent(): void {
		Functions\when( 'wc_get_order' )->justReturn(
			$this->order( [ CheckoutContext::META_KEY => [ 'consent' => CheckoutContext::DENIED ] ] )
		);

		OrderEnrich::enrich( 42 );

		$this->assertSame( [], $this->sent );
	}

	public function test_skips_orders_without_a_captured_checkout_context(): void {
		Functions\when( 'wc_get_order' )->justReturn( $this->order() );

		OrderEnrich::enrich( 42 );

		$this->assertSame( [], $this->sent );
	}
}

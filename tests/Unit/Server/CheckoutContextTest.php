<?php
/**
 * Tests for the checkout-time context capture.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Server\CheckoutContext;
use Mockery;

/**
 * @covers \LightweightPlugins\Pixel\Server\CheckoutContext
 * @covers \LightweightPlugins\Pixel\Server\ClientRequest
 */
final class CheckoutContextTest extends CapiTestCase {

	public function test_capture_stores_the_customers_request_on_the_order(): void {
		$_SERVER['REMOTE_ADDR']     = '203.0.113.7';
		$_SERVER['HTTP_USER_AGENT'] = 'CustomerBrowser/1.0';
		$_COOKIE['_fbp']            = 'fb.1.111.customer';

		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'update_meta_data' )->once()->with(
			CheckoutContext::META_KEY,
			[
				'consent' => CheckoutContext::GRANTED,
				'ip'  => '203.0.113.7',
				'ua'  => 'CustomerBrowser/1.0',
				'fbp' => 'fb.1.111.customer',
				'fbc' => '',
				'url' => 'https://shop.test/checkout/',
			]
		);
		$order->shouldReceive( 'save' )->once();

		CheckoutContext::capture_classic( 42, [], $order );
	}

	/**
	 * With LW Cookie active, its consent decision (read from the visitor's
	 * cookie in the checkout request) governs the server-side Purchase.
	 * A refusal stores only the refusal, no browser identifiers.
	 */
	public function test_refused_marketing_consent_stores_only_the_refusal(): void {
		$_SERVER['REMOTE_ADDR']     = '203.0.113.7';
		$_SERVER['HTTP_USER_AGENT'] = 'CustomerBrowser/1.0';
		add_filter( 'lw_cookie_is_category_allowed', '__return_false', 10, 2 );

		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'update_meta_data' )->once()->with(
			CheckoutContext::META_KEY,
			[ 'consent' => CheckoutContext::DENIED ]
		);
		$order->shouldReceive( 'save' )->once();

		CheckoutContext::capture( $order );
	}

	public function test_consent_filter_can_refuse(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'CustomerBrowser/1.0';
		Filters\expectApplied( 'lw_pixel_capi_purchase_consent' )->once()->andReturn( false );

		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'update_meta_data' )->once()->with(
			CheckoutContext::META_KEY,
			[ 'consent' => CheckoutContext::DENIED ]
		);
		$order->shouldReceive( 'save' )->once();

		CheckoutContext::capture( $order );
	}

	public function test_get_ignores_a_refused_context(): void {
		$order = $this->order( [ CheckoutContext::META_KEY => [ 'consent' => CheckoutContext::DENIED ] ] );

		$this->assertSame( [], CheckoutContext::get( $order ) );
	}

	public function test_get_ignores_a_context_without_a_consent_record(): void {
		$order = $this->order( [ CheckoutContext::META_KEY => [ 'ua' => 'UA', 'ip' => '1.1.1.1' ] ] );

		$this->assertSame( [], CheckoutContext::get( $order ) );
	}

	public function test_forged_proxy_header_is_ignored_by_default(): void {
		$_SERVER['REMOTE_ADDR']          = '203.0.113.7';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';

		$this->assertSame( '203.0.113.7', CheckoutContext::from_request()['ip'] );

		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	}

	public function test_get_returns_nothing_without_a_user_agent(): void {
		$order = $this->order( [ CheckoutContext::META_KEY => [ 'consent' => CheckoutContext::GRANTED, 'ip' => '1.1.1.1' ] ] );

		$this->assertSame( [], CheckoutContext::get( $order ) );
	}

	public function test_to_user_data_drops_empty_fields(): void {
		$this->assertSame(
			[
				'client_ip_address' => '1.1.1.1',
				'client_user_agent' => 'UA',
			],
			CheckoutContext::to_user_data( [ 'ip' => '1.1.1.1', 'ua' => 'UA', 'fbp' => '', 'fbc' => '' ] )
		);
	}

	public function test_non_order_argument_is_ignored(): void {
		Functions\expect( 'wc_get_checkout_url' )->never();

		CheckoutContext::capture( null );

		$this->addToAssertionCount( 1 );
	}
}

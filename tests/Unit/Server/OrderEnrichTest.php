<?php
/**
 * Tests for the server-side Purchase sent on order status changes.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Actions;
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

		OrderEnrich::send( 42 );

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

		OrderEnrich::send( 42 );

		$this->assertSame( [], $this->sent );
	}

	public function test_skips_orders_without_a_captured_checkout_context(): void {
		Functions\when( 'wc_get_order' )->justReturn( $this->order() );

		OrderEnrich::send( 42 );

		$this->assertSame( [], $this->sent );
	}

	public function test_enrich_queues_the_send_instead_of_blocking_checkout(): void {
		Functions\expect( 'as_enqueue_async_action' )
			->once()
			->with( OrderEnrich::ASYNC_HOOK, [ 42 ], 'lw-pixel', false )
			->andReturn( 7 );

		OrderEnrich::enrich( 42 );

		$this->assertSame( [], $this->sent );
	}

	/**
	 * Action Scheduler refused the action (e.g. processing, then completed in
	 * the same request): nothing runs inside the hook, and the shutdown sends
	 * the order once, not once per trigger.
	 */
	public function test_a_refused_enqueue_sends_once_at_shutdown(): void {
		Functions\when( 'as_enqueue_async_action' )->justReturn( 0 );
		Functions\when( 'wc_get_order' )->justReturn( $this->order( [ CheckoutContext::META_KEY => self::CUSTOMER_CONTEXT ] ) );
		Actions\expectAdded( 'shutdown' )->once();

		OrderEnrich::enrich( 42 );
		OrderEnrich::enrich( 42 );
		$this->assertSame( [], $this->sent, 'Nothing is sent inside the status hook.' );

		OrderEnrich::flush_deferred();
		OrderEnrich::flush_deferred();
		$this->assertCount( 1, $this->sent );
	}

	public function test_marks_the_order_tracked_when_meta_accepts_the_event(): void {
		$order = $this->order( [ CheckoutContext::META_KEY => self::CUSTOMER_CONTEXT ] );
		$order->shouldReceive( 'update_meta_data' )->once()->with( '_lw_pixel_capi_purchase_tracked', '1' );
		// Saved once for the flag, then again when the no longer needed context is dropped.
		$order->shouldReceive( 'save' )->twice();
		$order->shouldReceive( 'delete_meta_data' )->once()->with( CheckoutContext::META_KEY );
		Functions\when( 'wc_get_order' )->justReturn( $order );

		OrderEnrich::send( 42 );

		$this->assertCount( 1, $this->sent );
	}

	/**
	 * A rejected or failed call must not mark the order, so the next status
	 * change can retry instead of silently losing the Purchase.
	 */
	public function test_does_not_mark_the_order_tracked_when_meta_returns_an_error(): void {
		$this->status = 400;
		$order        = $this->order( [ CheckoutContext::META_KEY => self::CUSTOMER_CONTEXT ] );
		$order->shouldReceive( 'update_meta_data' )->never();
		$order->shouldReceive( 'save' )->never();
		Functions\when( 'wc_get_order' )->justReturn( $order );

		OrderEnrich::send( 42 );

		$this->assertCount( 1, $this->sent );
	}

	public function test_does_not_send_an_already_tracked_order(): void {
		Functions\when( 'wc_get_order' )->justReturn(
			$this->order(
				[
					CheckoutContext::META_KEY          => self::CUSTOMER_CONTEXT,
					'_lw_pixel_capi_purchase_tracked' => '1',
				]
			)
		);

		OrderEnrich::send( 42 );

		$this->assertSame( [], $this->sent );
	}

	/**
	 * A concurrent request (e.g. the payment webhook) holds the order lock:
	 * this one must not send a second Purchase.
	 */
	public function test_does_not_send_while_another_request_holds_the_order_lock(): void {
		$GLOBALS['wpdb'] = $this->wpdb( '0' );
		Functions\expect( 'wc_get_order' )->never();

		OrderEnrich::send( 42 );

		$this->assertSame( [], $this->sent );
	}
}

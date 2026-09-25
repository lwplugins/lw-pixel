<?php
/**
 * Tests for server-side event capture (scopes, consent, dedup ids).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Server\DispatchQueue;
use LightweightPlugins\Pixel\Server\EventDispatcher;

/**
 * @covers \LightweightPlugins\Pixel\Server\EventDispatcher
 * @covers \LightweightPlugins\Pixel\Server\DispatchQueue
 * @covers \LightweightPlugins\Pixel\Server\Providers\MetaProvider
 * @covers \LightweightPlugins\Pixel\Server\Providers\MetaCustomData
 */
final class EventDispatcherTest extends CapiTestCase {

	protected function setUp(): void {
		parent::setUp();
		DispatchQueue::reset();
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_get_current_user' )->justReturn( new class() { public function exists(): bool { return false; } } ); // phpcs:ignore
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'has_filter' )->justReturn( false );
		Functions\when( 'wp_doing_cron' )->justReturn( false );
		$_SERVER['REMOTE_ADDR']     = '203.0.113.7';
		$_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';
		$_SERVER['REQUEST_URI']     = '/cart/';
	}

	protected function tearDown(): void {
		DispatchQueue::reset();
		parent::tearDown();
	}

	/**
	 * A cron job or a script (e.g. a user import) is no visitor's action.
	 */
	public function test_nothing_is_queued_from_cron(): void {
		Functions\when( 'wp_doing_cron' )->justReturn( true );

		$this->assertFalse( EventDispatcher::capture( 'CompleteRegistration', [], 'evt-1', EventDispatcher::SCOPE_VISITOR ) );
		$this->assertSame( [], DispatchQueue::pending() );
	}

	public function test_nothing_is_queued_without_a_user_agent(): void {
		unset( $_SERVER['HTTP_USER_AGENT'] );

		$this->assertFalse( EventDispatcher::capture( 'CompleteRegistration', [], 'evt-1', EventDispatcher::SCOPE_VISITOR ) );
	}

	public function test_visitor_event_is_queued_for_meta_with_the_shared_event_id(): void {
		$queued = EventDispatcher::capture(
			'AddToCart',
			[
				'content_id' => '7',
				'quantity'   => 2,
				'price'      => 10.0,
				'currency'   => 'EUR',
			],
			'evt-1',
			EventDispatcher::SCOPE_VISITOR
		);

		$this->assertTrue( $queued );
		$event = DispatchQueue::pending()['fb'][0];
		$this->assertSame( 'AddToCart', $event['event_name'] );
		$this->assertSame( 'evt-1', $event['event_id'] );
		$this->assertSame( '203.0.113.7', $event['user_data']['client_ip_address'] );
		$this->assertSame( 'https://shop.test/cart/', $event['event_source_url'] );
		$this->assertSame( [ '7' ], $event['custom_data']['content_ids'] );
		$this->assertSame( 20.0, $event['custom_data']['value'] );
		$this->assertSame( [], $this->sent, 'Sending happens at shutdown, not inline.' );
	}

	/**
	 * A page view on a page a cache may serve to others must not get a
	 * server copy: the shared id would drop every later visitor's event.
	 *
	 * Separate process: an earlier capture defines DONOTCACHEPAGE.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_page_event_on_a_cacheable_page_is_not_captured(): void {
		$this->assertFalse( EventDispatcher::capture( 'PageView', [], 'evt-2', EventDispatcher::SCOPE_PAGE ) );
		$this->assertSame( [], DispatchQueue::pending() );
	}

	public function test_page_event_for_a_logged_in_visitor_is_captured(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$this->assertTrue( EventDispatcher::capture( 'PageView', [], 'evt-3', EventDispatcher::SCOPE_PAGE ) );
	}

	public function test_refused_consent_blocks_the_server_copy(): void {
		Functions\when( 'has_filter' )->justReturn( 10 );
		add_filter( 'lw_cookie_is_category_allowed', '__return_false', 10, 2 );

		$this->assertFalse( EventDispatcher::capture( 'Lead', [], 'evt-4', EventDispatcher::SCOPE_VISITOR ) );
		$this->assertSame( [], DispatchQueue::pending() );
	}

	public function test_non_standard_meta_events_stay_browser_only(): void {
		$this->assertFalse( EventDispatcher::capture( 'ViewCart', [], 'evt-5', EventDispatcher::SCOPE_VISITOR ) );
	}

	public function test_browser_scope_and_inactive_providers_capture_nothing(): void {
		$this->assertFalse( EventDispatcher::capture( 'Purchase', [], 'evt-6', EventDispatcher::SCOPE_BROWSER ) );

		$this->use_options( [ 'fb_capi_enabled' => false ] );
		$this->assertFalse( EventDispatcher::capture( 'Lead', [], 'evt-7', EventDispatcher::SCOPE_VISITOR ) );
	}

	public function test_send_now_posts_one_batch_per_provider(): void {
		EventDispatcher::capture( 'Lead', [], 'a', EventDispatcher::SCOPE_VISITOR );
		EventDispatcher::capture( 'Contact', [], 'b', EventDispatcher::SCOPE_VISITOR );

		DispatchQueue::send_now( DispatchQueue::pending() );

		$this->assertCount( 1, $this->sent );
		$this->assertCount( 2, $this->sent[0]['data'] );
	}

	public function test_stored_batch_is_sent_once_from_action_scheduler(): void {
		$store = [ 'lw_pixel_srv_x' => [ 'fb' => [ [ 'event_name' => 'Lead' ] ] ] ];
		Functions\when( 'get_transient' )->alias( static fn ( $key ) => $store[ $key ] ?? false );
		Functions\expect( 'delete_transient' )->once()->with( 'lw_pixel_srv_x' );

		DispatchQueue::send_stored( 'lw_pixel_srv_x' );

		$this->assertCount( 1, $this->sent );
	}
}

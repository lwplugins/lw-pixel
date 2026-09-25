<?php
/**
 * Tests for the event queue's print tracking and late events.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Events;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\Events\EventManager;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\PixelManager;
use LightweightPlugins\Pixel\Server\EventDispatcher;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Events\EventManager
 */
final class EventManagerTest extends MonkeyTestCase {

	private EventManager $manager;

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\when( 'get_option' )->justReturn(
			[
				'fb_enabled'  => true,
				'fb_pixel_id' => '123',
			]
		);
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);

		$this->manager = new EventManager( new PixelManager(), new ConsentManager() );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_emit_callback_runs_only_after_the_event_is_printed(): void {
		$calls = 0;
		$this->manager->queue( 'Purchase', [], static function () use ( &$calls ): void {
			++$calls;
		} );

		$this->assertSame( 0, $calls );

		$this->manager->mark_printed();
		$this->manager->mark_printed();

		$this->assertSame( 1, $calls );
	}

	/**
	 * Classic themes: woocommerce_thankyou queues the Purchase after the
	 * head island was printed; it must come back as a late event.
	 */
	public function test_events_queued_after_the_head_payload_are_late_events(): void {
		$this->manager->queue( 'PageView' );
		$this->manager->mark_printed();
		$this->manager->queue( 'Purchase', [ 'value' => 10.0 ] );

		$late = $this->manager->build_late_events();

		$this->assertCount( 1, $late );
		$this->assertSame( 'Purchase', $late[0]['name'] );
		$this->assertSame( 'Purchase', $late[0]['mapped']['fb']['name'] );
	}

	public function test_no_late_events_when_everything_was_printed(): void {
		$this->manager->queue( 'PageView' );
		$this->manager->mark_printed();

		$this->assertSame( [], $this->manager->build_late_events() );
	}

	public function test_event_without_a_server_copy_prints_no_event_id(): void {
		$this->manager->queue( 'PageView' );

		$this->assertArrayNotHasKey( 'event_id', $this->manager->get_queue()[0] );
	}

	public function test_browser_scope_keeps_a_fixed_event_id(): void {
		$this->manager->queue( 'Purchase', [ 'value' => 1.0 ], null, EventDispatcher::SCOPE_BROWSER, 'order-abc-1' );

		$this->assertSame( 'order-abc-1', $this->manager->build_late_events()[0]['event_id'] );
	}
}

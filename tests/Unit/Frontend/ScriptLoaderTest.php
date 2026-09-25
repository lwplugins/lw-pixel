<?php
/**
 * Tests for the late (footer) data island.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Frontend;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\Events\EventManager;
use LightweightPlugins\Pixel\Frontend\ScriptLoader;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\PixelManager;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Frontend\ScriptLoader
 */
final class ScriptLoaderTest extends MonkeyTestCase {

	private EventManager $events;

	private ScriptLoader $loader;

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
		Functions\when( 'wp_json_encode' )->alias( static fn ( $data, $flags = 0 ): string => (string) json_encode( $data, $flags ) );
		Functions\when( 'esc_attr' )->returnArg();

		$pixels       = new PixelManager();
		$this->events = new EventManager( $pixels, new ConsentManager() );
		$this->loader = new ScriptLoader( $pixels, $this->events );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_prints_events_queued_after_wp_head_and_marks_them_emitted(): void {
		Functions\when( 'did_action' )->justReturn( 1 );
		$emitted = false;
		$this->events->queue( 'Purchase', [ 'value' => 10.0 ], static function () use ( &$emitted ): void {
			$emitted = true;
		} );

		$this->expectOutputRegex( '/<script id="lw-pixel-late" type="application\/json">.*"Purchase".*<\/script>/' );

		$this->loader->print_late_payload();

		$this->assertTrue( $emitted );
	}

	/**
	 * Without a head island there is no runtime payload to merge into:
	 * nothing is printed and the event is not reported as emitted.
	 */
	public function test_does_nothing_when_wp_head_never_ran(): void {
		Functions\when( 'did_action' )->justReturn( 0 );
		$emitted = false;
		$this->events->queue( 'Purchase', [], static function () use ( &$emitted ): void {
			$emitted = true;
		} );

		$this->expectOutputString( '' );

		$this->loader->print_late_payload();

		$this->assertFalse( $emitted );
	}

	/**
	 * The localized nonce only served a logged-out AJAX endpoint that leaked
	 * draft/private product data; neither may come back.
	 */
	public function test_enqueue_prints_no_ajax_nonce(): void {
		Functions\expect( 'wp_enqueue_script' )->once();
		Functions\expect( 'wp_localize_script' )->never();
		Functions\expect( 'wp_create_nonce' )->never();

		$this->loader->enqueue();
	}
}

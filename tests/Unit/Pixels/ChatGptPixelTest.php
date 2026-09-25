<?php
/**
 * Tests for the ChatGPT Ads browser pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Pixels;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\ChatGptPixel;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Pixels\ChatGptPixel
 */
final class ChatGptPixelTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\when( 'get_option' )->justReturn(
			[
				'chatgpt_enabled'  => true,
				'chatgpt_pixel_id' => 'px_1',
				'chatgpt_debug'    => true,
			]
		);
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'is_user_logged_in' )->justReturn( false );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_configured_and_frontend_config(): void {
		$pixel = new ChatGptPixel();

		$this->assertTrue( $pixel->is_configured() );
		$this->assertSame( [ 'pixelId' => 'px_1', 'debug' => true ], $pixel->get_frontend_config() );
	}

	public function test_custom_event_carries_its_custom_event_name_option(): void {
		$mapped = ( new ChatGptPixel() )->map_event( 'Booked Demo', [] );

		$this->assertSame( 'custom', $mapped['name'] );
		$this->assertSame( [ 'custom_event_name' => 'booked_demo' ], $mapped['options'] );
	}

	public function test_unmapped_builtin_event_is_skipped(): void {
		$this->assertNull( ( new ChatGptPixel() )->map_event( 'Scroll', [] ) );
	}
}

<?php
/**
 * Tests for the retired classic custom event screens.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Admin\CustomEventScreen;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Admin\CustomEventScreen
 */
final class CustomEventScreenTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_key' )->alias( static fn ( $key ): string => strtolower( (string) $key ) );
		Functions\when( 'absint' )->alias( static fn ( $value ): int => abs( (int) $value ) );
		Functions\when( 'get_post_type' )->alias( static fn ( $id ): string => 7 === $id ? 'lw_pixel_event' : 'post' );
	}

	/**
	 * @dataProvider provide_screens
	 *
	 * @param string               $screen   Admin file.
	 * @param array<string, mixed> $query    Query args.
	 * @param bool                 $expected Redirected.
	 */
	public function test_only_custom_event_screens_are_redirected( string $screen, array $query, bool $expected ): void {
		$this->assertSame( $expected, CustomEventScreen::is_custom_event_screen( $screen, $query ) );
	}

	/**
	 * @return array<string, array{0: string, 1: array<string, mixed>, 2: bool}>
	 */
	public static function provide_screens(): array {
		return [
			'event list'      => [ 'edit.php', [ 'post_type' => 'lw_pixel_event' ], true ],
			'add new event'   => [ 'post-new.php', [ 'post_type' => 'lw_pixel_event' ], true ],
			'edit an event'   => [ 'post.php', [ 'post' => '7', 'action' => 'edit' ], true ],
			'post list'       => [ 'edit.php', [], false ],
			'page list'       => [ 'edit.php', [ 'post_type' => 'page' ], false ],
			'edit a post'     => [ 'post.php', [ 'post' => '3', 'action' => 'edit' ], false ],
			'post.php no id'  => [ 'post.php', [], false ],
		];
	}

	public function test_target_is_the_react_custom_events_tab(): void {
		Functions\when( 'admin_url' )->alias( static fn ( $path ): string => 'https://shop.test/wp-admin/' . $path );

		$this->assertSame( 'https://shop.test/wp-admin/admin.php?page=lw-pixel#custom-events', CustomEventScreen::target() );
	}
}

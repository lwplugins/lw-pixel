<?php
/**
 * Tests for the custom events cache.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\CustomEvents;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\CustomEvents\EventCache;
use LightweightPlugins\Pixel\CustomEvents\MetaBoxes;
use LightweightPlugins\Pixel\CustomEvents\PostType;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;
use Mockery;

/**
 * @covers \LightweightPlugins\Pixel\CustomEvents\EventCache
 */
final class EventCacheTest extends MonkeyTestCase {

	public function test_a_cached_list_is_returned_without_a_query(): void {
		$cached = [
			[
				'id'   => 5,
				'data' => [ 'event_name' => 'Signup' ],
			],
		];
		Functions\when( 'get_transient' )->justReturn( $cached );
		Functions\expect( 'set_transient' )->never();

		$this->assertSame( $cached, EventCache::all() );
	}

	public function test_writing_the_event_settings_drops_the_cache(): void {
		Functions\expect( 'delete_transient' )->once()->with( EventCache::KEY );

		EventCache::on_meta( 1, 5, MetaBoxes::META_KEY );
		EventCache::on_meta( 1, 5, '_edit_lock' );
	}

	public function test_a_status_change_of_an_event_drops_the_cache(): void {
		$event            = Mockery::mock( 'WP_Post' );
		$event->post_type = PostType::SLUG;
		$page             = Mockery::mock( 'WP_Post' );
		$page->post_type  = 'page';
		Functions\expect( 'delete_transient' )->once()->with( EventCache::KEY );

		EventCache::on_status( 'trash', 'publish', $event );
		EventCache::on_status( 'publish', 'draft', $page );
	}
}

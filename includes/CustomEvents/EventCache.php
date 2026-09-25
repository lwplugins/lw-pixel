<?php
/**
 * Cache of the published custom events.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CustomEvents;

/**
 * The published custom events are read on every frontend page, so they
 * are kept in one non-expiring transient (autoloaded, or in the object
 * cache) instead of a WP_Query per page. It is dropped whenever an event
 * post changes status, is deleted, or its settings meta is written — by
 * the React editor, the REST API or any other code.
 */
final class EventCache {

	public const KEY = 'lw_pixel_custom_events';

	/**
	 * Register the invalidation hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		foreach ( [ 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ] as $hook ) {
			add_action( $hook, [ self::class, 'on_meta' ], 10, 3 );
		}

		add_action( 'transition_post_status', [ self::class, 'on_status' ], 10, 3 );
		add_action( 'deleted_post', [ self::class, 'on_delete' ], 10, 2 );
	}

	/**
	 * Published events with a name: [ ['id' => int, 'data' => array], ... ].
	 *
	 * @return array<int, array{id: int, data: array<string, mixed>}>
	 */
	public static function all(): array {
		$cached = get_transient( self::KEY );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$query = new \WP_Query(
			[
				'post_type'      => PostType::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
			]
		);

		$events = [];

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$data = MetaBoxes::get_data( $post->ID );

			if ( '' !== $data['event_name'] ) {
				$events[] = [
					'id'   => (int) $post->ID,
					'data' => $data,
				];
			}
		}

		set_transient( self::KEY, $events );

		return $events;
	}

	/**
	 * Drop the cache.
	 *
	 * @return void
	 */
	public static function flush(): void {
		delete_transient( self::KEY );
	}

	/**
	 * Post meta written or deleted.
	 *
	 * @param mixed $meta_id   Meta id(s) (unused).
	 * @param mixed $object_id Post id (unused).
	 * @param mixed $meta_key  Meta key.
	 * @return void
	 */
	public static function on_meta( $meta_id, $object_id, $meta_key ): void {
		unset( $meta_id, $object_id );

		if ( MetaBoxes::META_KEY === $meta_key ) {
			self::flush();
		}
	}

	/**
	 * A post changed status (published, drafted, trashed, restored).
	 *
	 * @param mixed $new_status New status (unused).
	 * @param mixed $old_status Old status (unused).
	 * @param mixed $post       Post.
	 * @return void
	 */
	public static function on_status( $new_status, $old_status, $post ): void {
		unset( $new_status, $old_status );
		self::on_delete( 0, $post );
	}

	/**
	 * A post was deleted.
	 *
	 * @param mixed $post_id Post id (unused).
	 * @param mixed $post    Post.
	 * @return void
	 */
	public static function on_delete( $post_id, $post ): void {
		unset( $post_id );

		if ( $post instanceof \WP_Post && PostType::SLUG === $post->post_type ) {
			self::flush();
		}
	}
}

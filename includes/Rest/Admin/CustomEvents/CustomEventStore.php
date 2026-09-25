<?php
/**
 * Custom event storage for the admin API.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\CustomEvents;

use LightweightPlugins\Pixel\CustomEvents\MetaBoxes;
use LightweightPlugins\Pixel\CustomEvents\PostType;
use WP_Post;

/**
 * Reads and writes custom events in the storage the frontend already uses:
 * one lw_pixel_event post per event (published = enabled, draft = off) and
 * its `_lw_pixel_custom_event` meta record.
 */
final class CustomEventStore {

	/**
	 * Most events listed (the frontend runs the first 100 published ones).
	 */
	public const MAX_LISTED = 500;

	/**
	 * Every event (published and draft), newest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all(): array {
		$posts = get_posts(
			[
				'post_type'        => PostType::SLUG,
				'post_status'      => [ 'publish', 'draft' ],
				'posts_per_page'   => self::MAX_LISTED,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => true,
			]
		);

		return array_map( [ self::class, 'present' ], $posts );
	}

	/**
	 * One event post, or null when the ID is not a live custom event.
	 *
	 * @param int $id Post ID.
	 * @return WP_Post|null
	 */
	public static function find( int $id ): ?WP_Post {
		$post = get_post( $id );

		if ( ! $post instanceof WP_Post || PostType::SLUG !== $post->post_type || ! in_array( $post->post_status, [ 'publish', 'draft' ], true ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * Create or update an event from validated input.
	 *
	 * @param CustomEventInput $input Validated input.
	 * @param WP_Post|null     $post  Existing post, or null to create.
	 * @return int|\WP_Error Post ID.
	 */
	public static function write( CustomEventInput $input, ?WP_Post $post ) {
		$data   = $input->data();
		$fields = $input->post();
		$args   = [ 'post_type' => PostType::SLUG ];

		if ( null !== $post ) {
			$args['ID'] = $post->ID;
		}

		if ( array_key_exists( 'enabled', $fields ) ) {
			$args['post_status'] = $fields['enabled'] ? 'publish' : 'draft';
		} elseif ( null === $post ) {
			$args['post_status'] = 'publish';
		}

		if ( array_key_exists( 'title', $fields ) || null === $post ) {
			$title              = (string) ( $fields['title'] ?? '' );
			$args['post_title'] = '' !== $title ? $title : (string) $data['event_name'];
		}

		// Both core writers unslash their input: slash it so a backslash in a
		// title or an escaped selector (".a\:b") survives.
		$id = null === $post ? wp_insert_post( wp_slash( $args ), true ) : wp_update_post( wp_slash( $args ), true );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		update_post_meta( (int) $id, MetaBoxes::META_KEY, wp_slash( $data ) );

		return (int) $id;
	}

	/**
	 * REST shape of one event.
	 *
	 * @param WP_Post $post Event post.
	 * @return array<string, mixed>
	 */
	public static function present( WP_Post $post ): array {
		$data = MetaBoxes::get_data( $post->ID );

		return [
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'enabled'  => 'publish' === $post->post_status,
			'modified' => (string) $post->post_modified_gmt,
			'data'     => [
				'event_name'   => (string) $data['event_name'],
				'trigger_type' => in_array( $data['trigger_type'], CustomEventInput::TRIGGERS, true ) ? $data['trigger_type'] : 'page_load',
				'selector'     => (string) $data['selector'],
				'scroll_pct'   => (int) $data['scroll_pct'],
				'time_seconds' => (int) $data['time_seconds'],
				'page_pattern' => (string) $data['page_pattern'],
				'value'        => (string) $data['value'],
				'currency'     => (string) $data['currency'],
				'fire_once'    => (bool) $data['fire_once'],
			],
		];
	}
}

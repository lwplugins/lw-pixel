<?php
/**
 * Custom Event post type registrar.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CustomEvents;

/**
 * Registers the lw_pixel_event CPT used to manage user-defined events.
 */
final class PostType {

	public const SLUG = 'lw_pixel_event';

	/**
	 * Register the custom post type.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_post_type(
			self::SLUG,
			[
				'labels'              => self::get_labels(),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => false,
				'capabilities'        => self::admin_only_caps(),
				'hierarchical'        => false,
				'supports'            => [ 'title' ],
				'has_archive'         => false,
				'rewrite'             => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
			]
		);
	}

	/**
	 * Restrict every CPT capability to `manage_options`.
	 *
	 * Custom events drive frontend pixel logic — only admins should be able to
	 * add or edit them. Default `capability_type=post` would let any author or
	 * editor create event entries through wp-admin/edit.php?post_type=lw_pixel_event.
	 *
	 * @return array<string, string>
	 */
	private static function admin_only_caps(): array {
		$cap  = 'manage_options';
		$keys = [
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'publish_posts',
			'read_private_posts',
			'create_posts',
			'delete_posts',
			'delete_private_posts',
			'delete_published_posts',
			'delete_others_posts',
			'edit_private_posts',
			'edit_published_posts',
		];

		return array_fill_keys( $keys, $cap );
	}

	/**
	 * Localised labels.
	 *
	 * @return array<string, string>
	 */
	private static function get_labels(): array {
		return [
			'name'               => __( 'Custom Events', 'lw-pixel' ),
			'singular_name'      => __( 'Custom Event', 'lw-pixel' ),
			'add_new'            => __( 'Add New', 'lw-pixel' ),
			'add_new_item'       => __( 'Add New Custom Event', 'lw-pixel' ),
			'edit_item'          => __( 'Edit Custom Event', 'lw-pixel' ),
			'new_item'           => __( 'New Custom Event', 'lw-pixel' ),
			'view_item'          => __( 'View Custom Event', 'lw-pixel' ),
			'search_items'       => __( 'Search Custom Events', 'lw-pixel' ),
			'not_found'          => __( 'No custom events found.', 'lw-pixel' ),
			'not_found_in_trash' => __( 'No custom events in trash.', 'lw-pixel' ),
		];
	}
}

<?php
/**
 * Custom Event metaboxes.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CustomEvents;

/**
 * Renders and saves the Custom Event editor metabox.
 */
final class MetaBoxes {

	private const NONCE_KEY    = 'lw_pixel_custom_event_nonce';
	private const NONCE_ACTION = 'lw_pixel_save_custom_event';

	/**
	 * Meta key (single).
	 */
	public const META_KEY = '_lw_pixel_custom_event';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'add_meta_boxes', [ self::class, 'add_meta_boxes' ] );
		add_action( 'save_post_' . PostType::SLUG, [ self::class, 'save' ], 10, 2 );
	}

	/**
	 * Register the metabox.
	 *
	 * @return void
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'lw-pixel-custom-event',
			__( 'Event configuration', 'lw-pixel' ),
			[ self::class, 'render' ],
			PostType::SLUG,
			'normal',
			'high'
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public static function render( \WP_Post $post ): void {
		$data = self::get_data( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_KEY );

		MetaBoxRenderer::render( $data );
	}

	/**
	 * Save metabox data.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 * @return void
	 */
	public static function save( int $post_id, \WP_Post $post ): void {
		unset( $post );

		if ( ! isset( $_POST[ self::NONCE_KEY ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_KEY ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		update_post_meta( $post_id, self::META_KEY, MetaBoxSanitizer::sanitize( $_POST ) );
	}

	/**
	 * Read stored data with defaults.
	 *
	 * @param int $post_id Post id.
	 * @return array<string, mixed>
	 */
	public static function get_data( int $post_id ): array {
		$saved = get_post_meta( $post_id, self::META_KEY, true );

		return array_merge( self::defaults(), is_array( $saved ) ? $saved : [] );
	}

	/**
	 * Default values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return [
			'event_name'   => '',
			'trigger_type' => 'page_load',
			'selector'     => '',
			'scroll_pct'   => 50,
			'time_seconds' => 30,
			'page_pattern' => '',
			'value'        => '',
			'currency'     => 'USD',
			'fire_once'    => false,
		];
	}
}

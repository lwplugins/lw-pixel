<?php
/**
 * Retires the classic custom event screens.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin;

use LightweightPlugins\Pixel\CustomEvents\PostType;

/**
 * Custom events are edited in the React admin (LW Pixel → Custom Events).
 * The classic list, "Add New" and post edit screens of the lw_pixel_event
 * post type still exist (the events are stored as posts), so an old
 * bookmark or link is sent to the React editor instead.
 */
final class CustomEventScreen {

	/**
	 * Register the screen hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		foreach ( [ 'edit.php', 'post-new.php', 'post.php' ] as $screen ) {
			add_action( 'load-' . $screen, [ self::class, 'maybe_redirect' ] );
		}
	}

	/**
	 * Redirect a classic custom event screen to the React editor.
	 *
	 * @return void
	 */
	public static function maybe_redirect(): void {
		global $pagenow;

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( (string) $_SERVER['REQUEST_METHOD'] ) ) : 'get';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing decision, nothing is changed.
		if ( 'get' !== $method || ! self::is_custom_event_screen( (string) $pagenow, wp_unslash( $_GET ) ) ) {
			return;
		}

		wp_safe_redirect( self::target() );
		exit;
	}

	/**
	 * Whether the request shows a classic custom event screen.
	 *
	 * @param string               $screen Admin file (edit.php, post-new.php, post.php).
	 * @param array<string, mixed> $query  Query args.
	 * @return bool
	 */
	public static function is_custom_event_screen( string $screen, array $query ): bool {
		if ( 'post.php' === $screen ) {
			$post_id = absint( $query['post'] ?? 0 );

			return $post_id > 0 && PostType::SLUG === get_post_type( $post_id );
		}

		return in_array( $screen, [ 'edit.php', 'post-new.php' ], true )
			&& PostType::SLUG === sanitize_key( (string) ( $query['post_type'] ?? '' ) );
	}

	/**
	 * The React editor's Custom Events tab.
	 *
	 * @return string
	 */
	public static function target(): string {
		return admin_url( 'admin.php?page=' . SettingsPage::SLUG . '#custom-events' );
	}
}

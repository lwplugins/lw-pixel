<?php
/**
 * Auto-tracked events (scroll, time, download, login, signup, comment).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

use LightweightPlugins\Pixel\Options;

/**
 * Coordinates auto-tracked events between PHP hooks and the JS runtime.
 */
final class AutoEvents {

	/**
	 * Register WP hooks that fire server-side and flag the next request.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( Options::get( 'event_login' ) ) {
			add_action( 'wp_login', [ self::class, 'flag_login' ], 10, 2 );
		}

		if ( Options::get( 'event_signup' ) ) {
			add_action( 'user_register', [ self::class, 'flag_signup' ] );
		}

		if ( Options::get( 'event_comment' ) ) {
			add_action( 'comment_post', [ self::class, 'flag_comment' ], 10, 3 );
		}
	}

	/**
	 * Build the auto-event configuration block sent to the runtime.
	 *
	 * @return array<string, mixed>
	 */
	public static function frontend_config(): array {
		return [
			'scroll'   => self::scroll_config(),
			'time'     => self::time_config(),
			'download' => self::download_config(),
			'pending'  => PendingEventStore::pop( PendingEventStore::current_owner() ),
		];
	}

	/**
	 * Scroll-tracking config.
	 *
	 * @return array<string, mixed>
	 */
	private static function scroll_config(): array {
		return [
			'enabled'    => (bool) Options::get( 'event_scroll' ),
			'thresholds' => self::parse_int_list( (string) Options::get( 'event_scroll_thresholds' ) ),
		];
	}

	/**
	 * Time-on-page config.
	 *
	 * @return array<string, mixed>
	 */
	private static function time_config(): array {
		return [
			'enabled'    => (bool) Options::get( 'event_time_on_page' ),
			'thresholds' => self::parse_int_list( (string) Options::get( 'event_time_thresholds' ) ),
		];
	}

	/**
	 * Download-tracking config.
	 *
	 * @return array<string, mixed>
	 */
	private static function download_config(): array {
		$exts = array_map(
			static fn ( string $ext ): string => strtolower( trim( $ext ) ),
			explode( ',', (string) Options::get( 'event_download_extensions' ) )
		);

		return [
			'enabled'    => (bool) Options::get( 'event_download' ),
			'extensions' => array_values( array_filter( $exts ) ),
		];
	}

	/**
	 * Login event flag.
	 *
	 * @param string   $username Username.
	 * @param \WP_User $user     User object.
	 * @return void
	 */
	public static function flag_login( string $username, \WP_User $user ): void {
		unset( $username );
		PendingEventStore::push(
			'u_' . (string) $user->ID,
			'Login',
			[ 'user_id' => (string) $user->ID ]
		);
	}

	/**
	 * Signup event flag.
	 *
	 * @param int $user_id User id.
	 * @return void
	 */
	public static function flag_signup( int $user_id ): void {
		PendingEventStore::push(
			'u_' . (string) $user_id,
			'CompleteRegistration',
			[ 'user_id' => (string) $user_id ]
		);
	}

	/**
	 * Comment event flag.
	 *
	 * @param int        $comment_id Comment id.
	 * @param int|string $approved   Approval state.
	 * @param array      $data       Comment data.
	 * @return void
	 */
	public static function flag_comment( int $comment_id, int|string $approved, array $data ): void {
		unset( $approved, $data );
		PendingEventStore::push(
			PendingEventStore::current_owner(),
			'Comment',
			[ 'comment_id' => (string) $comment_id ]
		);
	}

	/**
	 * Parse a comma-separated list of integers.
	 *
	 * @param string $list CSV.
	 * @return array<int, int>
	 */
	private static function parse_int_list( string $list ): array {
		$values = array_map( 'intval', array_map( 'trim', explode( ',', $list ) ) );
		$values = array_filter( $values, static fn ( int $v ): bool => $v > 0 );
		sort( $values );

		return array_values( array_unique( $values ) );
	}
}

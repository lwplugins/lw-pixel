<?php
/**
 * Per-order mutual exclusion for the server-side Purchase.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

/**
 * Wraps MySQL named locks (GET_LOCK / RELEASE_LOCK).
 *
 * Order meta cannot be written atomically ("add only if absent") through the
 * WC CRUD API, so the check → send → mark sequence is serialised with a
 * named lock instead. A payment webhook and the checkout request moving the
 * same order to processing/completed at the same moment can then no longer
 * both send the Purchase. The lock is tied to the DB connection, so it is
 * released automatically if the request dies.
 */
final class OrderLock {

	/**
	 * Try to take the lock without waiting.
	 *
	 * @param int $order_id Order id.
	 * @return bool True when this request now holds the lock.
	 */
	public static function acquire( int $order_id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a lock must hit the server, never a cache.
		return '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', self::name( $order_id ) ) );
	}

	/**
	 * Release the lock.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public static function release( int $order_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a lock must hit the server, never a cache.
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::name( $order_id ) ) );
	}

	/**
	 * Lock name, scoped to this site's table prefix (shared DB servers, multisite).
	 *
	 * @param int $order_id Order id.
	 * @return string
	 */
	private static function name( int $order_id ): string {
		global $wpdb;

		return 'lw_pixel_capi_' . md5( $wpdb->prefix ) . '_' . $order_id;
	}
}

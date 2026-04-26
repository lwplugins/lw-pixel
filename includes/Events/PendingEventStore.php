<?php
/**
 * Transient-backed store for events fired server-side that should be replayed
 * to the JS runtime on the visitor's next request.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

/**
 * Persists pending events keyed by visitor identity.
 */
final class PendingEventStore {

	private const TRANSIENT_PREFIX = 'lw_pixel_pending_';
	private const TTL              = 5 * MINUTE_IN_SECONDS;

	/**
	 * Push an event for a given visitor key.
	 *
	 * @param string               $owner  Visitor key.
	 * @param string               $name   Event name.
	 * @param array<string, mixed> $params Event params.
	 * @return void
	 */
	public static function push( string $owner, string $name, array $params ): void {
		$key     = self::TRANSIENT_PREFIX . $owner;
		$pending = get_transient( $key );

		if ( ! is_array( $pending ) ) {
			$pending = [];
		}

		$pending[] = [
			'name'   => $name,
			'params' => $params,
		];

		set_transient( $key, $pending, self::TTL );
	}

	/**
	 * Pop (read + delete) all pending events for the given visitor key.
	 *
	 * @param string $owner Visitor key.
	 * @return array<int, array{name: string, params: array<string, mixed>}>
	 */
	public static function pop( string $owner ): array {
		$key     = self::TRANSIENT_PREFIX . $owner;
		$pending = get_transient( $key );

		if ( ! is_array( $pending ) || [] === $pending ) {
			return [];
		}

		delete_transient( $key );

		return $pending;
	}

	/**
	 * Build a stable owner key for the current visitor.
	 *
	 * Logged-in users get their numeric ID; anonymous visitors get a hash of
	 * IP + UA, which is good enough for a one-shot transient.
	 *
	 * @return string
	 */
	public static function current_owner(): string {
		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			return 'u_' . (string) $user_id;
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return 'a_' . substr( md5( $ip . $ua ), 0, 16 );
	}
}

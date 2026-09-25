<?php
/**
 * Transient-backed store for events fired server-side that should be replayed
 * to the JS runtime on the visitor's next request.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;

/**
 * Persists pending events keyed by visitor identity.
 *
 * Logged-in users are keyed by user ID. Anonymous visitors get a random,
 * short-lived first-party token cookie, set only when an event is stored for
 * them — never an IP/user-agent hash, which several visitors behind the
 * same NAT, proxy or CDN share.
 */
final class PendingEventStore {

	public const COOKIE = 'lw_pixel_pending';

	private const TRANSIENT_PREFIX = 'lw_pixel_pending_';
	private const TTL              = 5 * MINUTE_IN_SECONDS;

	/**
	 * Token minted in this request (the cookie is not readable until the next one).
	 *
	 * @var string
	 */
	private static string $minted = '';

	/**
	 * Push an event for the visitor making the current request.
	 *
	 * Anonymous visitors are skipped unless they allow at least one tracking
	 * category, so no token cookie is set without consent.
	 *
	 * @param string               $name   Event name.
	 * @param array<string, mixed> $params Event params.
	 * @return void
	 */
	public static function push_for_current_visitor( string $name, array $params ): void {
		$owner = self::current_owner();

		if ( '' === $owner ) {
			$consent = new ConsentManager();

			if ( ! $consent->is_category_allowed( 'marketing' ) && ! $consent->is_category_allowed( 'analytics' ) ) {
				return;
			}

			$owner = self::mint_owner();
		}

		self::push( $owner, $name, $params );
	}

	/**
	 * Push an event for a given visitor key.
	 *
	 * @param string               $owner  Visitor key.
	 * @param string               $name   Event name.
	 * @param array<string, mixed> $params Event params.
	 * @return void
	 */
	public static function push( string $owner, string $name, array $params ): void {
		if ( '' === $owner ) {
			return;
		}

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
	 * A page carrying them is per-visitor, so it is flagged as not cacheable
	 * for page-cache plugins that honour DONOTCACHEPAGE.
	 *
	 * @param string $owner Visitor key.
	 * @return array<int, array{name: string, params: array<string, mixed>}>
	 */
	public static function pop( string $owner ): array {
		if ( '' === $owner ) {
			return [];
		}

		$key     = self::TRANSIENT_PREFIX . $owner;
		$pending = get_transient( $key );

		if ( ! is_array( $pending ) || [] === $pending ) {
			return [];
		}

		delete_transient( $key );

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- shared page-cache convention constant.
		}

		return $pending;
	}

	/**
	 * The owner key for the current visitor, or '' when an anonymous
	 * visitor has no token (nothing can be pending for them).
	 *
	 * @return string
	 */
	public static function current_owner(): string {
		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			return 'u_' . (string) $user_id;
		}

		$token = '' !== self::$minted ? self::$minted : self::cookie_token();

		return '' === $token ? '' : 'a_' . $token;
	}

	/**
	 * Create a token for an anonymous visitor and send it as a cookie.
	 *
	 * @return string Owner key.
	 */
	private static function mint_owner(): string {
		self::$minted = bin2hex( random_bytes( 16 ) );

		if ( ! headers_sent() ) {
			setcookie(
				self::COOKIE,
				self::$minted,
				[
					'expires'  => time() + self::TTL,
					'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? (string) COOKIE_DOMAIN : '',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				]
			);
		}

		return 'a_' . self::$minted;
	}

	/**
	 * A well-formed token from the request cookie, or ''.
	 *
	 * @return string
	 */
	private static function cookie_token(): string {
		$raw = isset( $_COOKIE[ self::COOKIE ] ) && is_string( $_COOKIE[ self::COOKIE ] )
			? sanitize_key( wp_unslash( $_COOKIE[ self::COOKIE ] ) )
			: '';

		return 1 === preg_match( '/^[a-f0-9]{32}$/', $raw ) ? $raw : '';
	}

	/**
	 * Forget the token minted in this request (tests / long-running processes).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$minted = '';
	}
}

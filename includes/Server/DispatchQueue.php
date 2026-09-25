<?php
/**
 * Buffers server-side events and sends them without delaying the page.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Events\EventId;

/**
 * Events captured during a request are sent once, at shutdown, one batch
 * per provider:
 * 1. after the response was flushed to the visitor (PHP-FPM / LiteSpeed),
 * 2. otherwise in the background through Action Scheduler (the batch waits
 *    in a short-lived transient, never in the action's arguments),
 * 3. otherwise inline, as the last resort.
 *
 * Nothing sent is logged: failures only report provider + HTTP status.
 */
final class DispatchQueue {

	public const ASYNC_HOOK = 'lw_pixel_server_send';

	private const TRANSIENT_PREFIX = 'lw_pixel_srv_';

	/**
	 * Pending events: provider id → built events.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $batches = [];

	/**
	 * Whether the shutdown flush is hooked.
	 *
	 * @var bool
	 */
	private static bool $hooked = false;

	/**
	 * Register the Action Scheduler callback.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( self::ASYNC_HOOK, [ self::class, 'send_stored' ] );
	}

	/**
	 * Add a built event for a provider.
	 *
	 * @param string               $provider Provider id.
	 * @param array<string, mixed> $event    Built event.
	 * @return void
	 */
	public static function add( string $provider, array $event ): void {
		self::$batches[ $provider ][] = $event;

		if ( ! self::$hooked ) {
			self::$hooked = true;
			add_action( 'shutdown', [ self::class, 'flush' ], PHP_INT_MAX );
		}
	}

	/**
	 * Events waiting to be sent (tests / diagnostics).
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public static function pending(): array {
		return self::$batches;
	}

	/**
	 * Send everything captured in this request.
	 *
	 * @return void
	 */
	public static function flush(): void {
		$batches       = self::$batches;
		self::$batches = [];

		if ( [] === $batches ) {
			return;
		}

		if ( ! self::finish_response() && self::enqueue( $batches ) ) {
			return;
		}

		self::send_now( $batches );
	}

	/**
	 * Action Scheduler callback: send a stored batch once.
	 *
	 * @param string $key Transient key.
	 * @return void
	 */
	public static function send_stored( string $key ): void {
		if ( ! str_starts_with( $key, self::TRANSIENT_PREFIX ) ) {
			return;
		}

		$batches = get_transient( $key );
		delete_transient( $key );

		if ( is_array( $batches ) ) {
			self::send_now( $batches );
		}
	}

	/**
	 * Send batches to their providers.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $batches Batches.
	 * @return void
	 */
	public static function send_now( array $batches ): void {
		foreach ( $batches as $id => $events ) {
			$provider = ServerProviders::get( (string) $id );

			if ( null === $provider || ! $provider->is_active() || [] === $events ) {
				continue;
			}

			$result = $provider->send( $events );
			self::report( (string) $id, $result['ok'], $result['status'] );
		}
	}

	/**
	 * Announce a result; log failures (provider + status only) when WP_DEBUG is on.
	 *
	 * @param string $provider Provider id.
	 * @param bool   $ok       Accepted.
	 * @param int    $status   HTTP status (0 = not sent / transport error).
	 * @return void
	 */
	public static function report( string $provider, bool $ok, int $status ): void {
		/**
		 * Fires after a server-side request.
		 *
		 * @param string $provider Provider id.
		 * @param bool   $ok       Accepted.
		 * @param int    $status   HTTP status.
		 */
		do_action( 'lw_pixel_server_response', $provider, $ok, $status );

		if ( ! $ok && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug-only; no payload, PII or secret is logged.
			error_log( sprintf( 'LW Pixel: server-side %s request failed (HTTP %d).', $provider, $status ) );
		}
	}

	/**
	 * Flush the response to the visitor so the requests below do not delay it.
	 *
	 * @return bool True when the visitor is no longer waiting.
	 */
	public static function finish_response(): bool {
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			return fastcgi_finish_request();
		}

		if ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
			return true;
		}

		return false;
	}

	/**
	 * Hand the batches to Action Scheduler.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $batches Batches.
	 * @return bool
	 */
	private static function enqueue( array $batches ): bool {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return false;
		}

		$key = self::TRANSIENT_PREFIX . EventId::generate();
		set_transient( $key, $batches, HOUR_IN_SECONDS );

		if ( as_enqueue_async_action( self::ASYNC_HOOK, [ $key ], 'lw-pixel' ) > 0 ) {
			return true;
		}

		delete_transient( $key );
		return false;
	}

	/**
	 * Drop pending events (tests / long-running processes).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$batches = [];
		self::$hooked  = false;
	}
}

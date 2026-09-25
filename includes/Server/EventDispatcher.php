<?php
/**
 * Server-side event dispatcher.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;

/**
 * Decides which providers get a server-side copy of an event and queues it
 * with the same event id as the browser copy.
 *
 * Scopes:
 * - visitor: the event belongs to this visitor's own request (form sent,
 *   product added to cart, cart/checkout page, signup, login). Always
 *   eligible.
 * - page: a page-level event (page view, content view, search). Only sent
 *   when the page cannot be served from a page cache (logged-in visitor or
 *   DONOTCACHEPAGE already set): a cached page would give every visitor the
 *   same event id and only the first would count. Filterable with
 *   `lw_pixel_server_page_event`.
 * - browser: never sent from here (the Purchase has its own, idempotent
 *   path: ServerPurchase).
 *
 * Consent: every provider is checked against its own pixel's consent
 * category for the visitor making the request, exactly like the browser.
 * Without a visitor request (WP-CLI, cron, no user agent) nothing is sent.
 */
final class EventDispatcher {

	public const SCOPE_PAGE    = 'page';
	public const SCOPE_VISITOR = 'visitor';
	public const SCOPE_BROWSER = 'browser';

	/**
	 * Queue the server-side copies of an event.
	 *
	 * @param string               $name     Generic event name.
	 * @param array<string, mixed> $params   Generic params.
	 * @param string               $event_id Event id shared with the browser copy.
	 * @param string               $scope    One of the SCOPE_* constants.
	 * @return bool True when at least one copy was queued (the caller must
	 *              then hand the same event id to the browser).
	 */
	public static function capture( string $name, array $params, string $event_id, string $scope ): bool {
		if ( self::SCOPE_BROWSER === $scope || '' === $event_id ) {
			return false;
		}

		$providers = ServerProviders::active();

		if ( [] === $providers || ! self::is_visitor_request()
			|| ( self::SCOPE_PAGE === $scope && ! self::page_is_private( $name ) ) ) {
			return false;
		}

		$consent = new ConsentManager();
		$context = null;
		$queued  = false;

		foreach ( $providers as $id => $provider ) {
			if ( ! $consent->is_pixel_allowed( $id ) ) {
				continue;
			}

			$context = $context ?? RequestContext::current();
			$event   = $provider->build( $name, $params, $event_id, $context );

			if ( null !== $event ) {
				DispatchQueue::add( $id, $event );
				$queued = true;
			}
		}

		if ( $queued ) {
			self::mark_private();
		}

		return $queued;
	}

	/**
	 * Whether a visitor's browser made this request. WP-CLI, cron and
	 * requests without a user agent (user imports, `wp user create`,
	 * scripts) are not a visitor's action: nothing is sent for them.
	 *
	 * @return bool
	 */
	private static function is_visitor_request(): bool {
		if ( ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() ) {
			return false;
		}

		return '' !== ClientRequest::user_agent();
	}

	/**
	 * Whether the current page is served only to this visitor.
	 *
	 * @param string $name Event name.
	 * @return bool
	 */
	private static function page_is_private( string $name ): bool {
		$private = is_user_logged_in() || ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE );

		/**
		 * Filter whether a page-level event may be sent server-side.
		 *
		 * Return true only when the page is never served from a cache.
		 *
		 * @param bool   $private Default: logged-in visitor or DONOTCACHEPAGE.
		 * @param string $name    Event name.
		 */
		return (bool) apply_filters( 'lw_pixel_server_page_event', $private, $name );
	}

	/**
	 * The page now carries a per-visitor event id: keep it out of page caches.
	 *
	 * @return void
	 */
	private static function mark_private(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- shared page-cache convention constant.
		}
	}
}

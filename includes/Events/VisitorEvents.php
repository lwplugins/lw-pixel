<?php
/**
 * Events caused by a visitor action handled on the server.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

use LightweightPlugins\Pixel\Server\EventDispatcher;

/**
 * A form submission, signup, login or comment: the server-side copies are
 * sent from this (the visitor's own) request, and the browser copy is
 * delivered on their next page view with the same event id.
 */
final class VisitorEvents {

	/**
	 * Record an event for the visitor making the current request.
	 *
	 * @param string               $name   Event name.
	 * @param array<string, mixed> $params Event params.
	 * @return void
	 */
	public static function record( string $name, array $params ): void {
		$event_id = EventId::generate();
		$server   = EventDispatcher::capture( $name, $params, $event_id, EventDispatcher::SCOPE_VISITOR );

		PendingEventStore::push_for_current_visitor( $name, $params, $server ? $event_id : '' );
	}

	/**
	 * Record an event for a known user.
	 *
	 * @param int                  $user_id User whose browser gets the event.
	 * @param string               $name    Event name.
	 * @param array<string, mixed> $params  Event params.
	 * @param bool                 $server  Also send server-side (only when
	 *                                      the current request is that user's).
	 * @return void
	 */
	public static function record_for_user( int $user_id, string $name, array $params, bool $server = true ): void {
		$event_id = EventId::generate();
		$sent     = $server && EventDispatcher::capture( $name, $params, $event_id, EventDispatcher::SCOPE_VISITOR );

		PendingEventStore::push( 'u_' . (string) $user_id, $name, $params, $sent ? $event_id : '' );
	}
}

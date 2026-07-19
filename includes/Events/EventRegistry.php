<?php
/**
 * Event registry — collects which generic events should fire on the current request.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

/**
 * Build the active event list for the current request.
 */
final class EventRegistry {

	/**
	 * Built-in event class names (no params).
	 *
	 * @var array<int, class-string<EventInterface>>
	 */
	private const BUILTIN_EVENTS = [
		PageView::class,
		Search::class,
		ViewContent::class,
		ThankYou::class,
	];

	/**
	 * Resolve all events that should fire on this request.
	 *
	 * @return array<int, EventInterface>
	 */
	public static function resolve(): array {
		$events = [];

		foreach ( self::BUILTIN_EVENTS as $class ) {
			$event = new $class();

			if ( $event->should_fire() ) {
				$events[] = $event;
			}
		}

		/**
		 * Filter to register additional events.
		 *
		 * @param array<int, EventInterface> $events Resolved events.
		 */
		return (array) apply_filters( 'lw_pixel_resolve_events', $events );
	}
}

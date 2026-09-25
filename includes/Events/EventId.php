<?php
/**
 * Event IDs shared by the browser and server copies of an event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

/**
 * Meta (`eventID` / `event_id`), ChatGPT Ads (`event_id` / `id`) and the
 * GA4 `event_id` parameter use the same value for both copies, so the ad
 * platform keeps one of them.
 *
 * An ID must be unique per visitor and event: never print one into a page
 * that a page cache may serve to other visitors (ChatGPT Ads keeps only the
 * first event per pixel + event name + ID).
 */
final class EventId {

	/**
	 * A new random ID.
	 *
	 * @return string
	 */
	public static function generate(): string {
		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * The deterministic ID of an order's purchase, identical wherever and
	 * whenever it is computed (thank-you page, order status change, retry).
	 * Scoped to the site, so two shops sharing a pixel never collide.
	 *
	 * @param int $order_id Order id.
	 * @return string
	 */
	public static function for_order( int $order_id ): string {
		return 'order-' . substr( md5( (string) home_url( '/' ) ), 0, 8 ) . '-' . $order_id;
	}
}

<?php
/**
 * Custom Event resolver — assembles the JS payload entries.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CustomEvents;

/**
 * Exposes the published custom events matching the current page as
 * JS-runnable descriptors.
 */
final class EventResolver {

	/**
	 * Build the array of resolved custom event descriptors.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function resolve(): array {
		$current = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
			: '/';

		$events = [];

		foreach ( EventCache::all() as $event ) {
			if ( UrlPattern::matches( (string) $event['data']['page_pattern'], $current ) ) {
				$events[] = self::to_descriptor( $event['id'], $event['data'] );
			}
		}

		return $events;
	}

	/**
	 * Convert stored data to a JS-friendly descriptor.
	 *
	 * @param int                  $post_id Post id.
	 * @param array<string, mixed> $data    Stored data.
	 * @return array<string, mixed>
	 */
	private static function to_descriptor( int $post_id, array $data ): array {
		return [
			'id'           => $post_id,
			'name'         => $data['event_name'],
			'trigger_type' => $data['trigger_type'],
			'selector'     => $data['selector'],
			'scroll_pct'   => (int) $data['scroll_pct'],
			'time_seconds' => (int) $data['time_seconds'],
			'fire_once'    => (bool) $data['fire_once'],
			'params'       => array_filter(
				[
					'value'    => $data['value'],
					'currency' => $data['currency'],
				]
			),
		];
	}
}

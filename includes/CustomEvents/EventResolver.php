<?php
/**
 * Custom Event resolver — assembles the JS payload entries.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CustomEvents;

/**
 * Loads all published custom events and exposes them as JS-runnable descriptors.
 */
final class EventResolver {

	/**
	 * Build the array of resolved custom event descriptors.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function resolve(): array {
		$query = new \WP_Query(
			[
				'post_type'      => PostType::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
			]
		);

		$events = [];

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$data = MetaBoxes::get_data( $post->ID );

			if ( '' === $data['event_name'] ) {
				continue;
			}

			if ( ! self::matches_current_url( (string) $data['page_pattern'] ) ) {
				continue;
			}

			$events[] = self::to_descriptor( $post->ID, $data );
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

	/**
	 * Check whether the current request matches the configured URL pattern.
	 *
	 * Patterns: empty = all, "/products/*" = wildcard, exact = string match.
	 *
	 * @param string $pattern Configured URL pattern.
	 * @return bool
	 */
	private static function matches_current_url( string $pattern ): bool {
		if ( '' === $pattern ) {
			return true;
		}

		$current = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) )
			: '/';

		// Translate * wildcards to regex.
		$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';

		return 1 === preg_match( $regex, $current );
	}
}

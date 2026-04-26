<?php
/**
 * Custom event metabox sanitiser.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CustomEvents;

/**
 * Sanitises the submitted custom-event metabox data.
 */
final class MetaBoxSanitizer {

	private const TRIGGER_TYPES = [ 'page_load', 'click', 'scroll', 'time' ];

	/**
	 * Sanitise input.
	 *
	 * @param array<string, mixed> $post Raw $_POST data.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $post ): array {
		$raw = isset( $post['lw_pixel'] ) && is_array( $post['lw_pixel'] ) ? wp_unslash( $post['lw_pixel'] ) : [];

		$trigger_type = (string) ( $raw['trigger_type'] ?? 'page_load' );
		if ( ! in_array( $trigger_type, self::TRIGGER_TYPES, true ) ) {
			$trigger_type = 'page_load';
		}

		return [
			'event_name'   => sanitize_text_field( (string) ( $raw['event_name'] ?? '' ) ),
			'trigger_type' => $trigger_type,
			'selector'     => sanitize_text_field( (string) ( $raw['selector'] ?? '' ) ),
			'scroll_pct'   => max( 1, min( 100, (int) ( $raw['scroll_pct'] ?? 50 ) ) ),
			'time_seconds' => max( 1, (int) ( $raw['time_seconds'] ?? 30 ) ),
			'page_pattern' => sanitize_text_field( (string) ( $raw['page_pattern'] ?? '' ) ),
			'value'        => sanitize_text_field( (string) ( $raw['value'] ?? '' ) ),
			'currency'     => strtoupper( substr( sanitize_text_field( (string) ( $raw['currency'] ?? 'USD' ) ), 0, 3 ) ),
			'fire_once'    => ! empty( $raw['fire_once'] ),
		];
	}
}

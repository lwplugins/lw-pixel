<?php
/**
 * GTM dataLayer initializer.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Frontend;

/**
 * Helpers for emitting dataLayer entries server-side.
 */
final class DataLayer {

	/**
	 * Build a `<script>` tag that initialises window.dataLayer with the given payload.
	 *
	 * @param array<int, array<string, mixed>> $entries Initial entries.
	 * @return string
	 */
	public static function init_script( array $entries ): string {
		$json = wp_json_encode( $entries );

		return sprintf(
			'<script>window.dataLayer = window.dataLayer || []; window.dataLayer.push.apply(window.dataLayer, %s);</script>',
			$json
		);
	}
}

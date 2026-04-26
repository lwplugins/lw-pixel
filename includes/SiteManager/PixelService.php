<?php
/**
 * Service-layer callbacks for Pixel abilities.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\SiteManager;

use LightweightPlugins\Pixel\Admin\SettingsSanitizer;
use LightweightPlugins\Pixel\Options;

use function LightweightPlugins\Pixel\lw_pixel;

/**
 * Implements the Abilities API callbacks.
 */
final class PixelService {

	/**
	 * Get all options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options(): array {
		return [
			'success' => true,
			'options' => Options::get_all(),
		];
	}

	/**
	 * Update options.
	 *
	 * Submitted values run through SettingsSanitizer so that the same rules used
	 * by the admin form (capability check on raw HTML keys, type coercion, etc.)
	 * apply to ability calls — they cannot bypass head/body/footer protections.
	 *
	 * @param array<string, mixed> $input Input payload (must contain `options`).
	 * @return array<string, mixed>
	 */
	public static function set_options( array $input ): array {
		$updates = (array) ( $input['options'] ?? [] );

		if ( [] === $updates ) {
			return [
				'success' => false,
				'message' => __( 'No options provided.', 'lw-pixel' ),
				'updated' => [],
			];
		}

		$current   = Options::get_all();
		$known     = array_intersect_key( $updates, Options::get_defaults() );
		$merged    = array_merge( $current, $known );
		$sanitized = SettingsSanitizer::sanitize( $merged );

		Options::save( $sanitized );

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of updated keys */
				__( 'Updated %d option(s).', 'lw-pixel' ),
				count( $known )
			),
			'updated' => array_keys( $known ),
		];
	}

	/**
	 * Return all pixel providers and their status.
	 *
	 * @return array<string, mixed>
	 */
	public static function list_pixels(): array {
		$plugin = lw_pixel();
		$pixels = [];

		foreach ( $plugin->get_pixel_manager()->all() as $pixel ) {
			$pixels[ $pixel->get_id() ] = [
				'label'      => $pixel->get_label(),
				'enabled'    => $pixel->is_enabled(),
				'configured' => $pixel->is_configured(),
			];
		}

		return [
			'success' => true,
			'pixels'  => $pixels,
		];
	}
}

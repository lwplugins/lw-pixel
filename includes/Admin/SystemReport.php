<?php
/**
 * System Report — diagnostics for support.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin;

use LightweightPlugins\Pixel\Options;

use function LightweightPlugins\Pixel\lw_pixel;

/**
 * Builds the diagnostic report shown (and downloadable) on the Tools screen.
 */
final class SystemReport {

	/**
	 * Custom code options: reported only as their length.
	 */
	private const CODE_KEYS = [ 'head_code', 'body_open_code', 'footer_code' ];

	/**
	 * Generate the full report payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function generate(): array {
		return [
			'environment'  => self::environment(),
			'pixels'       => self::pixels(),
			'integrations' => Integrations::detect(),
			'options'      => self::redacted_options(),
		];
	}

	/**
	 * Environment block.
	 *
	 * @return array<string, mixed>
	 */
	private static function environment(): array {
		global $wp_version;

		return [
			'php_version'   => PHP_VERSION,
			'wp_version'    => $wp_version,
			'lw_pixel'      => LW_PIXEL_VERSION,
			'site_url'      => home_url( '/' ),
			'is_multisite'  => is_multisite(),
			'is_https'      => is_ssl(),
			'memory_limit'  => ini_get( 'memory_limit' ),
			'max_execution' => ini_get( 'max_execution_time' ),
		];
	}

	/**
	 * Pixel status block.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function pixels(): array {
		$out = [];

		foreach ( lw_pixel()->get_pixel_manager()->all() as $pixel ) {
			$out[ $pixel->get_id() ] = [
				'label'      => $pixel->get_label(),
				'enabled'    => $pixel->is_enabled(),
				'configured' => $pixel->is_configured(),
			];
		}

		return $out;
	}

	/**
	 * Options without secrets or custom code: a report is pasted into
	 * support tickets, and custom code often carries third-party keys.
	 *
	 * @return array<string, mixed>
	 */
	private static function redacted_options(): array {
		$opts = Options::get_all();

		foreach ( array_merge( Options::SECRET_KEYS, self::CODE_KEYS ) as $key ) {
			if ( ! empty( $opts[ $key ] ) ) {
				$opts[ $key ] = '***REDACTED*** (' . strlen( (string) $opts[ $key ] ) . ' chars)';
			}
		}

		return $opts;
	}
}

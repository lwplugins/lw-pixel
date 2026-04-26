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
 * Builds a diagnostic report shown in the Settings → System Report tab.
 */
final class SystemReport {

	/**
	 * Generate the full report payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function generate(): array {
		return [
			'environment'  => self::environment(),
			'pixels'       => self::pixels(),
			'integrations' => self::integrations(),
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
	 * Detected integrations.
	 *
	 * @return array<string, bool>
	 */
	private static function integrations(): array {
		return [
			'woocommerce'     => class_exists( '\\WooCommerce' ),
			'lw_cookie'       => defined( 'LW_COOKIE_VERSION' ),
			'lw_site_manager' => defined( 'LW_SITE_MANAGER_VERSION' ),
			'cf7'             => defined( 'WPCF7_VERSION' ),
			'wpforms'         => defined( 'WPFORMS_VERSION' ),
			'elementor_pro'   => defined( 'ELEMENTOR_PRO_VERSION' ),
			'forminator'      => defined( 'FORMINATOR_VERSION' ),
			'formidable'      => class_exists( '\\FrmAppController' ),
			'ninja_forms'     => class_exists( '\\Ninja_Forms' ),
			'fluent_forms'    => defined( 'FLUENTFORM_VERSION' ),
			'ws_form'         => class_exists( '\\WS_Form' ),
			'gravity_forms'   => class_exists( '\\GFForms' ),
		];
	}

	/**
	 * Options with secrets redacted.
	 *
	 * @return array<string, mixed>
	 */
	private static function redacted_options(): array {
		$secrets = [ 'fb_capi_token', 'ga4_mp_api_secret' ];
		$opts    = Options::get_all();

		foreach ( $secrets as $key ) {
			if ( ! empty( $opts[ $key ] ) ) {
				$opts[ $key ] = '***REDACTED*** (' . strlen( (string) $opts[ $key ] ) . ' chars)';
			}
		}

		return $opts;
	}
}

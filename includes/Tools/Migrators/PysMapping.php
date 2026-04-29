<?php
/**
 * PixelYourSite → lw-pixel field map.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tools\Migrators;

/**
 * Field-level mapping table from PYS option keys to lw-pixel option keys,
 * grouped per source bucket (`pys_facebook`, `pys_ga`, `pys_gtm`, …).
 *
 * Each entry is `pys_key => lw_key`. Booleans, strings, and ids pass through
 * verbatim; the migrator handles type coercion.
 */
final class PysMapping {

	/**
	 * Full mapping table.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function all(): array {
		return [
			'pys_facebook'  => self::facebook(),
			'pys_ga'        => self::google_analytics(),
			'pys_gtm'       => self::google_tag_manager(),
			'pys_pinterest' => self::pinterest(),
			'pys_bing'      => self::bing(),
			'pys_reddit'    => self::reddit(),
			'pys'           => self::core(),
		];
	}

	/**
	 * Facebook (Meta) pixel.
	 *
	 * @return array<string, string>
	 */
	private static function facebook(): array {
		return [
			'enabled'                   => 'fb_enabled',
			'pixel_id'                  => 'fb_pixel_id',
			'advanced_matching_enabled' => 'fb_advanced_matching',
			'send_external_id'          => 'fb_send_external_id',
			'use_server_api'            => 'fb_capi_enabled',
			'server_access_api_token'   => 'fb_capi_token',
		];
	}

	/**
	 * Google Analytics 4.
	 *
	 * @return array<string, string>
	 */
	private static function google_analytics(): array {
		return [
			'enabled'     => 'ga4_enabled',
			'tracking_id' => 'ga4_measurement_id',
		];
	}

	/**
	 * Google Tag Manager.
	 *
	 * @return array<string, string>
	 */
	private static function google_tag_manager(): array {
		return [
			'enabled'      => 'gtm_enabled',
			'gtm_id'       => 'gtm_container_id',
			'container_id' => 'gtm_container_id',
		];
	}

	/**
	 * Pinterest tag.
	 *
	 * @return array<string, string>
	 */
	private static function pinterest(): array {
		return [
			'enabled' => 'pinterest_enabled',
			'tag_id'  => 'pinterest_tag_id',
		];
	}

	/**
	 * Bing UET.
	 *
	 * @return array<string, string>
	 */
	private static function bing(): array {
		return [
			'enabled' => 'bing_enabled',
			'tag_id'  => 'bing_tag_id',
			'uet_id'  => 'bing_tag_id',
		];
	}

	/**
	 * Reddit.
	 *
	 * @return array<string, string>
	 */
	private static function reddit(): array {
		return [
			'enabled'  => 'reddit_enabled',
			'pixel_id' => 'reddit_pixel_id',
		];
	}

	/**
	 * Core PYS settings → cross-cutting lw-pixel options.
	 *
	 * @return array<string, string>
	 */
	private static function core(): array {
		return [
			'debug_enabled' => 'debug_mode',
		];
	}
}

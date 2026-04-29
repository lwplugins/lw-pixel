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
	 * Facebook (Meta) pixel — plus the auto-event and WooCommerce toggles that
	 * PYS happens to store inside the `pys_facebook` bucket alongside the FB
	 * pixel itself.
	 *
	 * @return array<string, string>
	 */
	private static function facebook(): array {
		return self::facebook_pixel()
			+ self::auto_events()
			+ self::woocommerce_events();
	}

	/**
	 * FB-specific pixel + Conversion API options.
	 *
	 * @return array<string, string>
	 */
	private static function facebook_pixel(): array {
		return [
			'enabled'                   => 'fb_enabled',
			'pixel_id'                  => 'fb_pixel_id',
			'advanced_matching_enabled' => 'fb_advanced_matching',
			'send_external_id'          => 'fb_send_external_id',
			'use_server_api'            => 'fb_capi_enabled',
			'server_access_api_token'   => 'fb_capi_token',
			'test_api_event_code'       => 'fb_test_event_code',
		];
	}

	/**
	 * Cross-cutting auto-event toggles. PYS keeps these in `pys_facebook`.
	 *
	 * @return array<string, string>
	 */
	private static function auto_events(): array {
		return [
			'general_event_enabled'                => 'event_pageview',
			'automatic_event_search_enabled'       => 'event_search',
			'automatic_event_form_enabled'         => 'event_lead',
			'automatic_event_login_enabled'        => 'event_login',
			'automatic_event_signup_enabled'       => 'event_signup',
			'automatic_event_comment_enabled'      => 'event_comment',
			'automatic_event_download_enabled'     => 'event_download',
			'automatic_event_scroll_enabled'       => 'event_scroll',
			'automatic_event_time_on_page_enabled' => 'event_time_on_page',
		];
	}

	/**
	 * WooCommerce event toggles + content-id prefix. Also stored under
	 * `pys_facebook` in PYS.
	 *
	 * @return array<string, string>
	 */
	private static function woocommerce_events(): array {
		return [
			'woo_view_content_enabled'      => 'woo_view_product',
			'woo_view_category_enabled'     => 'woo_view_category',
			'woo_add_to_cart_enabled'       => 'woo_add_to_cart',
			'woo_initiate_checkout_enabled' => 'woo_initiate_checkout',
			'woo_purchase_enabled'          => 'woo_purchase',
			'woo_content_id_prefix'         => 'woo_content_id_prefix',
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

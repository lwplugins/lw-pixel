<?php
/**
 * Default option values.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel;

/**
 * Static catalog of default option values, partitioned by feature area.
 */
final class DefaultOptions {

	/**
	 * Build the full defaults map.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		return array_merge(
			self::pixels(),
			self::events(),
			self::woocommerce(),
			self::forms(),
			self::consent(),
			self::compliance(),
			self::advanced()
		);
	}

	/**
	 * Pixel provider defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function pixels(): array {
		return [
			'fb_enabled'            => false,
			'fb_pixel_id'           => '',
			'fb_advanced_matching'  => false,
			'fb_capi_enabled'       => false,
			'fb_capi_token'         => '',
			'fb_test_event_code'    => '',
			'fb_send_external_id'   => false,
			'fb_order_enrich'       => false,
			'ga4_enabled'           => false,
			'ga4_measurement_id'    => '',
			'ga4_debug'             => false,
			'ga4_anonymize_ip'      => true,
			'ga4_mp_enabled'        => false,
			'ga4_mp_api_secret'     => '',
			'gads_enabled'          => false,
			'gads_conversion_id'    => '',
			'gads_conversion_label' => '',
			'gads_remarketing'      => false,
			'gtm_enabled'           => false,
			'gtm_container_id'      => '',
			'gtm_data_layer_only'   => false,
			'tiktok_enabled'        => false,
			'tiktok_pixel_id'       => '',
			'pinterest_enabled'     => false,
			'pinterest_tag_id'      => '',
			'pinterest_em_enabled'  => false,
			'bing_enabled'          => false,
			'bing_tag_id'           => '',
			'reddit_enabled'        => false,
			'reddit_pixel_id'       => '',
			'snapchat_enabled'      => false,
			'snapchat_pixel_id'     => '',
			'x_enabled'             => false,
			'x_pixel_id'            => '',
		];
	}

	/**
	 * Generic + auto-event defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function events(): array {
		return [
			'event_pageview'            => true,
			'event_view_content'        => true,
			'event_search'              => true,
			'event_lead'                => true,
			'event_scroll'              => false,
			'event_scroll_thresholds'   => '25,50,75,100',
			'event_time_on_page'        => false,
			'event_time_thresholds'     => '10,30,60,180',
			'event_download'            => false,
			'event_download_extensions' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip,mp3,mp4',
			'event_login'               => false,
			'event_signup'              => false,
			'event_comment'             => false,
			'event_click_phone'         => false,
			'event_click_email'         => false,
			'event_thankyou'            => false,
			'event_thankyou_urls'       => '',
		];
	}

	/**
	 * WooCommerce event defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function woocommerce(): array {
		return [
			'woo_view_product'        => true,
			'woo_view_category'       => true,
			'woo_view_cart'           => true,
			'woo_add_to_cart'         => true,
			'woo_initiate_checkout'   => true,
			'woo_add_payment_info'    => true,
			'woo_purchase'            => true,
			'woo_content_id_prefix'   => '',
			'woo_use_sku'             => false,
			'woo_send_value_with_tax' => true,
		];
	}

	/**
	 * Form-integration defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function forms(): array {
		return [
			'form_cf7'          => true,
			'form_wpforms'      => true,
			'form_elementor'    => true,
			'form_forminator'   => true,
			'form_formidable'   => true,
			'form_ninjaforms'   => true,
			'form_fluentforms'  => true,
			'form_wsform'       => true,
			'form_gravityforms' => true,
		];
	}

	/**
	 * Consent defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function consent(): array {
		return [
			'consent_mode'                => 'lw_cookie',
			'consent_marketing_pixels'    => [ 'fb', 'tiktok', 'pinterest', 'gads', 'reddit', 'snapchat', 'x' ],
			'consent_analytics_pixels'    => [ 'ga4', 'bing' ],
			'consent_unclassified_pixels' => [ 'gtm' ],
		];
	}

	/**
	 * Compliance defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function compliance(): array {
		return [
			'compliance_medical'  => false,
			'compliance_ldu'      => false,
			'compliance_ldu_mode' => 'auto',
		];
	}

	/**
	 * Advanced defaults.
	 *
	 * @return array<string, mixed>
	 */
	private static function advanced(): array {
		return [
			'head_code'          => '',
			'footer_code'        => '',
			'body_open_code'     => '',
			'disable_for_admins' => true,
			'debug_mode'         => false,
		];
	}
}

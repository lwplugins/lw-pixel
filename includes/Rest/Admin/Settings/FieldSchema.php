<?php
/**
 * Validation rules of the settings the admin API writes.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\Settings;

use LightweightPlugins\Pixel\Options;

/**
 * Pure data: one rule per option key. A key without an entry gets a rule
 * from its default's type (bool → bool, string → short text), so an option
 * added to DefaultOptions is writable without touching this file.
 */
final class FieldSchema {

	/**
	 * Characters a tracking ID may hold: no spaces, slashes or quotes, so an
	 * ID can never change a URL path or break out of a script.
	 */
	public const SAFE_ID = '/^[A-Za-z0-9_-]{1,64}$/';

	/**
	 * Options nothing reads at runtime: not written through the admin API
	 * (kept here until they leave DefaultOptions).
	 */
	private const RETIRED = [ 'debug_mode', 'gads_remarketing', 'pinterest_em_enabled', 'consent_mode' ];

	/**
	 * Largest custom code blob, in bytes.
	 */
	public const MAX_CODE_BYTES = 65536;

	/**
	 * Explicit rules.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function rules(): array {
		return [
			'fb_pixel_id'                 => self::id( '/^\d{5,20}$/' ),
			'fb_test_event_code'          => self::id( self::SAFE_ID ),
			'ga4_measurement_id'          => self::id( '/^G-[A-Z0-9]{4,20}$/', true ),
			'gads_conversion_id'          => self::id( '/^AW-\d{5,15}$/', true ),
			'gads_conversion_label'       => self::id( self::SAFE_ID ),
			'gtm_container_id'            => self::id( '/^GTM-[A-Z0-9]{4,12}$/', true ),
			'tiktok_pixel_id'             => self::id( self::SAFE_ID ),
			'pinterest_tag_id'            => self::id( self::SAFE_ID ),
			'bing_tag_id'                 => self::id( self::SAFE_ID ),
			'reddit_pixel_id'             => self::id( self::SAFE_ID ),
			'snapchat_pixel_id'           => self::id( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', false, true ),
			'x_pixel_id'                  => self::id( self::SAFE_ID ),
			'chatgpt_pixel_id'            => self::id( self::SAFE_ID ),
			'event_scroll_thresholds'     => [
				'type' => 'int_list',
				'min'  => 1,
				'max'  => 100,
			],
			'event_time_thresholds'       => [
				'type' => 'int_list',
				'min'  => 1,
				'max'  => 86400,
			],
			'event_download_extensions'   => [
				'type'    => 'word_list',
				'pattern' => '/^[a-z0-9]{1,10}$/',
			],
			'event_thankyou_urls'         => [
				'type'      => 'lines',
				'max_lines' => 50,
				'max_line'  => 200,
			],
			'woo_content_id_prefix'       => [
				'type' => 'text',
				'max'  => 64,
			],
			'compliance_ldu_mode'         => [
				'type'   => 'enum',
				'values' => [ 'auto', 'force_california' ],
			],
			'consent_marketing_pixels'    => [ 'type' => 'pixel_list' ],
			'consent_analytics_pixels'    => [ 'type' => 'pixel_list' ],
			'consent_unclassified_pixels' => [ 'type' => 'pixel_list' ],
			'head_code'                   => [ 'type' => 'raw' ],
			'body_open_code'              => [ 'type' => 'raw' ],
			'footer_code'                 => [ 'type' => 'raw' ],
		];
	}

	/**
	 * The consent category each consent list feeds (Consent\Manager).
	 *
	 * @return array<string, string>
	 */
	public static function consent_lists(): array {
		return [
			'consent_marketing_pixels'    => 'marketing',
			'consent_analytics_pixels'    => 'analytics',
			'consent_unclassified_pixels' => 'functional',
		];
	}

	/**
	 * Rule of one option key, or null for an unknown key.
	 *
	 * @param string $key Option key.
	 * @return array<string, mixed>|null
	 */
	public static function rule( string $key ): ?array {
		$defaults = Options::get_defaults();

		if ( ! array_key_exists( $key, $defaults ) || in_array( $key, self::RETIRED, true ) ) {
			return null;
		}

		if ( in_array( $key, Options::SECRET_KEYS, true ) ) {
			return [ 'type' => 'secret' ];
		}

		$rules = self::rules();

		if ( isset( $rules[ $key ] ) ) {
			return $rules[ $key ];
		}

		return is_bool( $defaults[ $key ] ) ? [ 'type' => 'bool' ] : [
			'type' => 'text',
			'max'  => 200,
		];
	}

	/**
	 * A tracking ID rule.
	 *
	 * @param string $pattern   Full-match pattern.
	 * @param bool   $uppercase Upper-case the input first (G-, AW-, GTM-).
	 * @param bool   $lowercase Lower-case the input first (UUIDs).
	 * @return array<string, mixed>
	 */
	private static function id( string $pattern, bool $uppercase = false, bool $lowercase = false ): array {
		return [
			'type'      => 'id',
			'pattern'   => $pattern,
			'uppercase' => $uppercase,
			'lowercase' => $lowercase,
		];
	}
}

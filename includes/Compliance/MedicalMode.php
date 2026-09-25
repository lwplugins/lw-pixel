<?php
/**
 * Medical traffic mode.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Compliance;

use LightweightPlugins\Pixel\Options;

/**
 * Strips identifiable params from event payloads / CAPI calls when the site is
 * marked as medical traffic. Useful for HIPAA-aware setups: removes user-agent,
 * IP, advanced matching values, and any URL parameter on a configured allowlist.
 *
 * Reference: Meta's "Health and wellness" guidance.
 */
final class MedicalMode {

	/**
	 * Register filters when medical mode is on.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! Options::get( 'compliance_medical' ) ) {
			return;
		}

		add_filter( 'lw_pixel_event_params', [ self::class, 'strip_event_params' ], 100, 2 );
		add_filter( 'lw_pixel_capi_user_data', [ self::class, 'strip_user_data' ], 100 );
		add_filter( 'lw_pixel_capi_event_body', [ self::class, 'strip_meta_event' ], 100 );
		add_filter( 'lw_pixel_chatgpt_capi_event', [ self::class, 'strip_chatgpt_event' ], 100 );
		add_filter( 'lw_pixel_ga4_mp_event', [ self::class, 'strip_ga4_event' ], 100 );
		add_filter( 'lw_pixel_chatgpt_browser_user', '__return_empty_array', 100 );
	}

	/**
	 * ChatGPT Ads Conversions API: keep only the pixel's own browser
	 * reference; drop IP, user agent, hashed identifiers, location and
	 * item names.
	 *
	 * @param array<string, mixed> $event Event body.
	 * @return array<string, mixed>
	 */
	public static function strip_chatgpt_event( array $event ): array {
		$event['user'] = array_intersect_key( (array) ( $event['user'] ?? [] ), [ 'obref' => true ] );

		if ( [] === $event['user'] ) {
			unset( $event['user'] );
		}

		foreach ( (array) ( $event['data']['contents'] ?? [] ) as $i => $item ) {
			unset( $event['data']['contents'][ $i ]['name'] );
		}

		return $event;
	}

	/**
	 * Meta Conversions API: drop the content name (product title, Lead form
	 * name) and the search term from custom_data.
	 *
	 * @param array<string, mixed> $event Server event.
	 * @return array<string, mixed>
	 */
	public static function strip_meta_event( array $event ): array {
		if ( isset( $event['custom_data'] ) && is_array( $event['custom_data'] ) ) {
			unset( $event['custom_data']['content_name'], $event['custom_data']['search_string'] );
		}

		return $event;
	}

	/**
	 * GA4 Measurement Protocol: drop page URL, search term and item names.
	 *
	 * @param array<string, mixed> $event Event ({name, params}).
	 * @return array<string, mixed>
	 */
	public static function strip_ga4_event( array $event ): array {
		unset( $event['params']['page_location'], $event['params']['search_term'] );

		foreach ( (array) ( $event['params']['items'] ?? [] ) as $i => $item ) {
			unset( $event['params']['items'][ $i ]['item_name'] );
		}

		return $event;
	}

	/**
	 * Drop URL params and body content from event params.
	 *
	 * @param array<string, mixed> $params Event params.
	 * @param string               $name   Event name.
	 * @return array<string, mixed>
	 */
	public static function strip_event_params( array $params, string $name ): array {
		unset( $name );

		$strip_keys = [ 'page_location', 'page_referrer', 'search_string', 'content_name' ];

		foreach ( $strip_keys as $key ) {
			unset( $params[ $key ] );
		}

		return $params;
	}

	/**
	 * Drop personally identifiable user_data from CAPI calls.
	 *
	 * @param array<string, mixed> $user_data User data.
	 * @return array<string, mixed>
	 */
	public static function strip_user_data( array $user_data ): array {
		$strip_keys = [
			'em',
			'ph',
			'fn',
			'ln',
			'ct',
			'st',
			'zp',
			'country',
			'ge',
			'db',
			'external_id',
			'client_ip_address',
			'client_user_agent',
		];

		foreach ( $strip_keys as $key ) {
			unset( $user_data[ $key ] );
		}

		return $user_data;
	}
}

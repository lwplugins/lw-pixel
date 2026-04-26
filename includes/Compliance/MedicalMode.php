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

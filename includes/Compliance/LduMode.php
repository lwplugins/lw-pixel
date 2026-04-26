<?php
/**
 * Limited Data Use (California / CCPA).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Compliance;

use LightweightPlugins\Pixel\Options;

/**
 * Adds the Meta data_processing_options block ("LDU") to CAPI events when LDU
 * mode is enabled. Lets the user opt all traffic in, or only California traffic
 * (Meta resolves geo automatically when {0,0} is sent).
 *
 * Reference: https://developers.facebook.com/docs/marketing-apis/data-processing-options
 */
final class LduMode {

	/**
	 * Register the CAPI body filter.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! Options::get( 'compliance_ldu' ) ) {
			return;
		}

		add_filter( 'lw_pixel_capi_event_body', [ self::class, 'attach_ldu' ] );
	}

	/**
	 * Attach LDU markers to a CAPI event body.
	 *
	 * @param array<string, mixed> $event Event body.
	 * @return array<string, mixed>
	 */
	public static function attach_ldu( array $event ): array {
		$mode = (string) Options::get( 'compliance_ldu_mode', 'auto' );

		// Auto: Meta detects geo (LDU + 0,0). Force: 1,1000 (California).
		$country = 0;
		$state   = 0;

		if ( 'force_california' === $mode ) {
			$country = 1;
			$state   = 1000;
		}

		$event['data_processing_options']         = [ 'LDU' ];
		$event['data_processing_options_country'] = $country;
		$event['data_processing_options_state']   = $state;

		return $event;
	}
}

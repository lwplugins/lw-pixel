<?php
/**
 * Google tag before consent, under Google Consent Mode v2.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Consent;

use LightweightPlugins\Pixel\Options;

/**
 * Opt-in "advanced" Consent Mode: the GA4 / Google Ads base tag (gtag.js)
 * loads for every visitor, so Google's tag checks find it. Consent Mode's
 * denied defaults keep it cookieless until the visitor consents; LW Pixel's
 * events still wait for consent. Only takes effect when Consent Mode
 * defaults are printed (LW Cookie's Google Consent Mode, or a filter).
 */
final class GoogleTagBeforeConsent {

	/**
	 * Host of the Google tag (gtag.js).
	 */
	public const HOST = 'googletagmanager.com';

	/**
	 * Hook into LW Cookie's blocker.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'lw_cookie_exempt_hosts', [ self::class, 'exempt_host' ] );
	}

	/**
	 * Whether the GA4 / Google Ads base tag loads before consent.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		if ( ! Options::get( 'google_tag_before_consent', false ) || ! self::has_google_tag() ) {
			return false;
		}

		return self::consent_mode_on();
	}

	/**
	 * Let LW Cookie's script blocker load the Google tag before consent.
	 *
	 * @param mixed $hosts Hosts LW Cookie loads without consent.
	 * @return array<int, string>
	 */
	public static function exempt_host( $hosts ): array {
		$hosts = array_values( array_map( 'strval', (array) $hosts ) );

		if ( self::is_active() && ! in_array( self::HOST, $hosts, true ) ) {
			$hosts[] = self::HOST;
		}

		return $hosts;
	}

	/**
	 * Whether GA4 or Google Ads is set up in LW Pixel.
	 *
	 * @return bool
	 */
	private static function has_google_tag(): bool {
		return ( Options::get( 'ga4_enabled' ) && '' !== (string) Options::get( 'ga4_measurement_id' ) )
			|| ( Options::get( 'gads_enabled' ) && '' !== (string) Options::get( 'gads_conversion_id' ) );
	}

	/**
	 * Whether Consent Mode v2 defaults are printed before the tag.
	 *
	 * @return bool
	 */
	private static function consent_mode_on(): bool {
		$getter   = [ 'LightweightPlugins\\Cookie\\Options', 'get' ];
		$detected = is_callable( $getter ) && (bool) call_user_func( $getter, 'gcm_enabled' );

		/**
		 * Whether Google Consent Mode v2 defaults are printed on the page.
		 * Another consent banner can return true here.
		 *
		 * @param bool $detected Whether LW Cookie's Google Consent Mode is on.
		 */
		return (bool) apply_filters( 'lw_pixel_consent_mode_active', $detected );
	}
}

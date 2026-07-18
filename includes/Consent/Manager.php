<?php
/**
 * Consent manager — bridges to LW Cookie or filters.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Consent;

use LightweightPlugins\Pixel\Options;

/**
 * Resolves consent state per pixel/category.
 */
final class Manager {

	private const CATEGORIES = [ 'necessary', 'functional', 'analytics', 'marketing' ];

	/**
	 * Categorized pixel map (cached).
	 *
	 * @var array<string, string>|null
	 */
	private ?array $pixel_categories = null;

	/**
	 * Whether a pixel id is allowed to fire.
	 *
	 * @param string $pixel_id Pixel slug (e.g. 'fb', 'ga4').
	 * @return bool
	 */
	public function is_pixel_allowed( string $pixel_id ): bool {
		$category = $this->category_for_pixel( $pixel_id );

		if ( '' === $category ) {
			return true;
		}

		return $this->is_category_allowed( $category );
	}

	/**
	 * Whether a consent category is allowed.
	 *
	 * @param string $category Category slug.
	 * @return bool
	 */
	public function is_category_allowed( string $category ): bool {
		if ( 'necessary' === $category ) {
			return true;
		}

		// LW Cookie integration — consume the lw-cookie hook directly.
		if ( has_filter( 'lw_cookie_is_category_allowed' ) ) {
			return (bool) apply_filters( 'lw_cookie_is_category_allowed', false, $category ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party (lw-cookie) hook.
		}

		// Generic filter for other consent plugins.
		return (bool) apply_filters( 'lw_pixel_is_category_allowed', true, $category );
	}

	/**
	 * Whether consent can be evaluated CLIENT-SIDE (cache-safe).
	 *
	 * LW Cookie publishes the visitor's consent in a JS-readable cookie
	 * (`lw_cookie_consent`) and fires a `lwCookieConsent` event, so runtime.js
	 * can decide per-visitor which pixels fire — without the server baking a
	 * cache-filler's consent into the HTML. We detect it via lw-cookie's PHP
	 * hook. When true, PayloadBuilder emits every configured pixel plus a
	 * pixel→category map and lets the browser gate; when false it keeps the
	 * server-side filter (no client-readable consent source to defer to).
	 *
	 * @return bool
	 */
	public function has_client_gating(): bool {
		return false !== has_filter( 'lw_cookie_is_category_allowed' );
	}

	/**
	 * Current consent state per category.
	 *
	 * @return array<string, bool>
	 */
	public function get_state(): array {
		$state = [];

		foreach ( self::CATEGORIES as $category ) {
			$state[ $category ] = $this->is_category_allowed( $category );
		}

		return $state;
	}

	/**
	 * Map of pixel id → consent category.
	 *
	 * @return array<string, string>
	 */
	public function get_pixel_categories(): array {
		if ( null === $this->pixel_categories ) {
			$this->pixel_categories = $this->build_pixel_categories();
		}

		return $this->pixel_categories;
	}

	/**
	 * Build the pixel → category map from options.
	 *
	 * @return array<string, string>
	 */
	private function build_pixel_categories(): array {
		$marketing    = (array) Options::get( 'consent_marketing_pixels' );
		$analytics    = (array) Options::get( 'consent_analytics_pixels' );
		$unclassified = (array) Options::get( 'consent_unclassified_pixels' );

		$map = [];

		foreach ( $marketing as $id ) {
			$map[ (string) $id ] = 'marketing';
		}

		foreach ( $analytics as $id ) {
			$map[ (string) $id ] = 'analytics';
		}

		foreach ( $unclassified as $id ) {
			$map[ (string) $id ] = 'functional';
		}

		return $map;
	}

	/**
	 * Resolve category for a pixel id.
	 *
	 * @param string $pixel_id Pixel slug.
	 * @return string Category slug or empty string when uncategorized.
	 */
	private function category_for_pixel( string $pixel_id ): string {
		$map = $this->get_pixel_categories();
		return $map[ $pixel_id ] ?? '';
	}
}

<?php
/**
 * Thank-you page event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

use LightweightPlugins\Pixel\Options;

/**
 * Fires the standard Lead event when the request URI matches one of the
 * configured thank-you page fragments.
 */
final class ThankYou extends AbstractEvent {

	public function __construct() {
		$this->params = [ 'page_url' => self::request_uri() ];
	}

	public function get_name(): string {
		return 'Lead';
	}

	protected function option_key(): string {
		return 'event_thankyou';
	}

	/**
	 * Fire only when the toggle is on AND the current URI matches a fragment.
	 *
	 * @return bool
	 */
	public function should_fire(): bool {
		if ( ! parent::should_fire() ) {
			return false;
		}

		$patterns = (string) Options::get( 'event_thankyou_urls', '' );

		return '' !== self::match_pattern( self::request_uri(), $patterns );
	}

	/**
	 * Return the first configured fragment contained in the URI, or ''.
	 *
	 * Matching is case-insensitive; blank lines are ignored.
	 *
	 * @param string $uri      Request URI (path plus query string).
	 * @param string $patterns Newline-separated fragments.
	 * @return string
	 */
	public static function match_pattern( string $uri, string $patterns ): string {
		$uri   = strtolower( $uri );
		$split = preg_split( '/\R/', $patterns );
		$lines = false === $split ? [] : $split;

		foreach ( $lines as $pattern ) {
			$trimmed = trim( (string) $pattern );

			if ( '' === $trimmed ) {
				continue;
			}

			if ( str_contains( $uri, strtolower( $trimmed ) ) ) {
				return $trimmed;
			}
		}

		return '';
	}

	/**
	 * Current request URI (path plus query), without scheme or host.
	 *
	 * @return string
	 */
	private static function request_uri(): string {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return '/';
		}

		return esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) );
	}
}

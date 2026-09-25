<?php
/**
 * Custom event page pattern matching.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CustomEvents;

/**
 * Matches a custom event's page pattern against a request URI.
 *
 * - empty: every page;
 * - `*` is a wildcard (`/products/*`);
 * - the query string and a trailing slash are ignored, so `/contact`
 *   matches `/contact/` and `/contact/?utm_source=x`; a pattern that has a
 *   `?` itself is matched against the path and query;
 * - a full URL pattern is reduced to its path (and query).
 */
final class UrlPattern {

	/**
	 * Whether the pattern matches the URI.
	 *
	 * @param string $pattern Configured pattern.
	 * @param string $uri     Request URI (path + optional query).
	 * @return bool
	 */
	public static function matches( string $pattern, string $uri ): bool {
		$pattern = trim( $pattern );

		if ( '' === $pattern ) {
			return true;
		}

		if ( preg_match( '#^[a-z][a-z0-9+.-]*://#i', $pattern ) ) {
			$query   = (string) wp_parse_url( $pattern, PHP_URL_QUERY );
			$pattern = (string) wp_parse_url( $pattern, PHP_URL_PATH ) . ( '' !== $query ? '?' . $query : '' );
		}

		$uri = strtok( $uri, '#' );
		$uri = false === $uri ? '' : $uri;

		if ( ! str_contains( $pattern, '?' ) ) {
			$pattern = self::trim_slash( $pattern );
			$uri     = self::trim_slash( (string) strtok( $uri, '?' ) );
		}

		$regex = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . '$#i';

		return 1 === preg_match( $regex, $uri );
	}

	/**
	 * Drop a trailing slash, keeping the root as "/".
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private static function trim_slash( string $path ): string {
		$trimmed = rtrim( $path, '/' );

		return '' === $trimmed ? '/' : $trimmed;
	}
}

<?php
/**
 * Google Ads conversion list: one "EventName=label" per line.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Parses the event → conversion label list of the Google Ads settings.
 */
final class GoogleAdsConversions {

	private const LINE = '/^([A-Za-z][A-Za-z0-9_]{0,63})\s*=\s*([A-Za-z0-9_-]{1,64})$/';

	/**
	 * Event name → conversion label.
	 *
	 * @param string $text Stored list.
	 * @return array<string, string>
	 */
	public static function parse( string $text ): array {
		$map = [];

		foreach ( self::lines( $text ) as $line ) {
			if ( preg_match( self::LINE, $line, $match ) ) {
				$map[ $match[1] ] = $match[2];
			}
		}

		return $map;
	}

	/**
	 * Lines that are not "EventName=label".
	 *
	 * @param string $text Submitted list.
	 * @return array<int, string>
	 */
	public static function invalid_lines( string $text ): array {
		return array_values( array_filter( self::lines( $text ), static fn ( string $line ): bool => 1 !== preg_match( self::LINE, $line ) ) );
	}

	/**
	 * Trimmed, non-empty lines.
	 *
	 * @param string $text Text.
	 * @return array<int, string>
	 */
	private static function lines( string $text ): array {
		$split = preg_split( '/\R/', $text );

		return array_values( array_filter( array_map( 'trim', false === $split ? [] : $split ), static fn ( string $line ): bool => '' !== $line ) );
	}
}

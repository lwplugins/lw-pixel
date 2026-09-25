<?php
/**
 * SHA-256 hashing utility for advanced matching user data.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\AdvancedMatching;

/**
 * Normalises and hashes user-data fields per Meta CAPI / Google EMP specs.
 *
 * Reference: https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/customer-information-parameters
 */
final class Hasher {

	/**
	 * Hash a single user-data value.
	 *
	 * @param string $field Field name (em, ph, fn, ln, ct, st, zp, country, ge, db, external_id).
	 * @param string $value Raw value.
	 * @return string|null SHA-256 hex digest, or null when input is empty.
	 */
	public static function hash( string $field, string $value ): ?string {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}

		$normalized = self::normalize( $field, $value );
		if ( '' === $normalized ) {
			return null;
		}

		return hash( 'sha256', $normalized );
	}

	/**
	 * Build a hashed user_data array from a raw associative array.
	 *
	 * @param array<string, string> $raw Raw user data (unhashed).
	 * @return array<string, string>
	 */
	public static function build_user_data( array $raw ): array {
		$out = [];

		// Meta: "Use only the first 5 digits for U.S. zip codes" (ZIP+4).
		if ( isset( $raw['zp'] ) && 'us' === self::lowercase( trim( (string) ( $raw['country'] ?? '' ) ) ) ) {
			$digits = (string) preg_replace( '/\D+/', '', (string) $raw['zp'] );
			if ( strlen( $digits ) >= 5 ) {
				$raw['zp'] = substr( $digits, 0, 5 );
			}
		}

		foreach ( $raw as $field => $value ) {
			$hashed = self::hash( (string) $field, (string) $value );
			if ( null !== $hashed ) {
				$out[ $field ] = $hashed;
			}
		}

		return $out;
	}

	/**
	 * UTF-8 aware lowercase (plain strtolower() leaves "É" unchanged, so
	 * the hash would not match Meta's).
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function lowercase( string $value ): string {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
	}

	/**
	 * Normalise a value before hashing.
	 *
	 * @param string $field Field name.
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function normalize( string $field, string $value ): string {
		$value = self::lowercase( $value );

		switch ( $field ) {
			case 'em':
				return filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : '';
			case 'ph':
				// Callers pass PhoneNormalizer output (country code included);
				// this only guarantees digits without leading zeroes.
				$digits = preg_replace( '/\D+/', '', $value );
				return is_string( $digits ) ? ltrim( $digits, '0' ) : '';
			case 'fn':
			case 'ln':
				// Meta: lowercase, no punctuation; UTF-8 characters are kept.
				$cleaned = preg_replace( '/[\s!-\/:-@\[-`{-~]+/u', '', $value );
				return is_string( $cleaned ) ? $cleaned : '';
			case 'ct':
				$cleaned = preg_replace( '/\s+/', '', $value );
				return is_string( $cleaned ) ? $cleaned : '';
			case 'st':
				return preg_replace( '/[^a-z]/', '', $value ) ?? '';
			case 'zp':
				// Meta: "lowercase with no spaces and no dash".
				return preg_replace( '/[\s-]+/', '', $value ) ?? '';
			case 'country':
				return substr( $value, 0, 2 );
			case 'ge':
				return 'm' === $value[0] ? 'm' : 'f';
			case 'db':
				return preg_replace( '/\D/', '', $value ) ?? '';
			default:
				return $value;
		}
	}
}

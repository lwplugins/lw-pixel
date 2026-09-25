<?php
/**
 * Phone number normalisation for hashed matching.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\AdvancedMatching;

/**
 * Produces the digits-only international form both Meta and ChatGPT Ads
 * hash: country calling code + national number, no "+", no leading zeroes
 * ("+36 (30) 123-4567" and "06 30 123 4567" with country HU → "36301234567").
 */
final class PhoneNormalizer {

	/**
	 * National trunk prefixes other than a plain leading "0", keyed by
	 * calling code (Hungary dials "06" before national numbers).
	 *
	 * @var array<int, string>
	 */
	private const TRUNK_PREFIXES = [
		'36' => '06',
	];

	/**
	 * Normalise a phone number.
	 *
	 * @param string $phone        Raw phone number.
	 * @param string $calling_code Country calling code of the customer's
	 *                             country ("36" or "+36"); used only when
	 *                             the number has no international prefix.
	 * @return string Digits (8–15), or '' when the result is not plausible.
	 */
	public static function normalize( string $phone, string $calling_code = '' ): string {
		$phone = (string) preg_replace( '/[\s().\-\/]+/', '', trim( $phone ) );

		if ( '' === $phone ) {
			return '';
		}

		if ( str_starts_with( $phone, '+' ) ) {
			$digits = self::digits( substr( $phone, 1 ) );
		} elseif ( str_starts_with( $phone, '00' ) ) {
			$digits = self::digits( substr( $phone, 2 ) );
		} else {
			$code     = self::digits( $calling_code );
			$national = self::digits( $phone );
			$trunk    = self::TRUNK_PREFIXES[ $code ] ?? '';

			if ( '' !== $trunk && str_starts_with( $national, $trunk ) ) {
				$national = substr( $national, strlen( $trunk ) );
			}

			$national = ltrim( $national, '0' );

			// Without a known country code the national number cannot be
			// made international: hashing it would never match.
			if ( '' === $code ) {
				return '';
			}

			$digits = $code . $national;
		}

		$digits = ltrim( $digits, '0' );
		$length = strlen( $digits );

		return $length >= 8 && $length <= 15 ? $digits : '';
	}

	/**
	 * Calling code (digits) for a 2-letter country, via WooCommerce.
	 *
	 * @param string $country ISO 3166-1 alpha-2 code.
	 * @return string
	 */
	public static function calling_code( string $country ): string {
		if ( '' === $country || ! function_exists( 'WC' ) || ! WC()->countries ) {
			return '';
		}

		$code = WC()->countries->get_country_calling_code( strtoupper( $country ) );

		if ( is_array( $code ) ) {
			$code = (string) reset( $code );
		}

		return self::digits( (string) $code );
	}

	/**
	 * Keep digits only.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function digits( string $value ): string {
		return (string) preg_replace( '/\D+/', '', $value );
	}
}

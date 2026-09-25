<?php
/**
 * ChatGPT Ads customer data: normalisation and hashing.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\ChatGpt;

use LightweightPlugins\Pixel\AdvancedMatching\Hasher;
use LightweightPlugins\Pixel\AdvancedMatching\PhoneNormalizer;

/**
 * Builds the `user` object for the Conversions API (plural lists) and the
 * pixel's init `user` option (singular fields), per the OpenAI docs
 * (checked 2026-09-26):
 * - email: trim + lowercase; phone: country code kept, leading "+" and
 *   zeroes removed, 8–15 digits; external id: trimmed, case preserved;
 *   names: lowercase, whitespace and ASCII punctuation removed, non-ASCII
 *   kept. Then UTF-8 + SHA-256 (lowercase hex).
 * - city / region: trimmed, lowercase, max 128; postal code max 32;
 *   country: 2 letters. Sent in plain text.
 *
 * Raw input keys: email, phone, calling_code, external_id, first_name,
 * last_name, city, region, postal_code, country.
 */
final class UserData {

	/**
	 * Conversions API `user` object (without ip/user agent/obref).
	 *
	 * @param array<string, string> $raw Raw customer data.
	 * @return array<string, array<int, string>>
	 */
	public static function for_capi( array $raw ): array {
		$fields = self::normalized( $raw );
		$out    = [];
		$map    = [
			'email'       => 'emails_sha256',
			'phone'       => 'phone_numbers_sha256',
			'external_id' => 'external_ids_sha256',
			'first_name'  => 'first_names_sha256',
			'last_name'   => 'last_names_sha256',
		];

		foreach ( $map as $field => $key ) {
			if ( '' !== $fields[ $field ] ) {
				$out[ $key ] = [ hash( 'sha256', $fields[ $field ] ) ];
			}
		}

		$plain = [
			'city'        => 'cities',
			'region'      => 'regions',
			'postal_code' => 'postal_codes',
			'country'     => 'countries',
		];

		foreach ( $plain as $field => $key ) {
			if ( '' !== $fields[ $field ] ) {
				$out[ $key ] = [ $fields[ $field ] ];
			}
		}

		return $out;
	}

	/**
	 * Pixel init `user` option.
	 *
	 * @param array<string, string> $raw Raw customer data.
	 * @return array<string, string>
	 */
	public static function for_browser( array $raw ): array {
		$fields = self::normalized( $raw );
		$out    = [];

		$hashed = [
			'email'        => 'email',
			'phone_number' => 'phone',
			'external_id'  => 'external_id',
			'first_name'   => 'first_name',
			'last_name'    => 'last_name',
		];

		foreach ( $hashed as $key => $field ) {
			if ( '' !== $fields[ $field ] ) {
				$out[ $key . '_sha256' ] = hash( 'sha256', $fields[ $field ] );
			}
		}

		foreach ( [ 'country', 'city', 'region', 'postal_code' ] as $field ) {
			if ( '' !== $fields[ $field ] ) {
				$out[ $field ] = $fields[ $field ];
			}
		}

		return $out;
	}

	/**
	 * Normalise every supported field ('' when absent or invalid).
	 *
	 * @param array<string, string> $raw Raw customer data.
	 * @return array<string, string>
	 */
	public static function normalized( array $raw ): array {
		$get   = static fn ( string $key ): string => trim( (string) ( $raw[ $key ] ?? '' ) );
		$email = Hasher::lowercase( $get( 'email' ) );

		return [
			'email'       => false !== strpos( $email, '@' ) ? $email : '',
			'phone'       => PhoneNormalizer::normalize( $get( 'phone' ), $get( 'calling_code' ) ),
			'external_id' => $get( 'external_id' ),
			'first_name'  => self::name( $get( 'first_name' ) ),
			'last_name'   => self::name( $get( 'last_name' ) ),
			'city'        => self::cut( Hasher::lowercase( $get( 'city' ) ), 0, 128 ),
			'region'      => self::cut( Hasher::lowercase( $get( 'region' ) ), 0, 128 ),
			'postal_code' => self::cut( $get( 'postal_code' ), 0, 32 ),
			'country'     => 1 === preg_match( '/^[A-Za-z]{2}$/', $get( 'country' ) ) ? strtoupper( $get( 'country' ) ) : '',
		];
	}

	/**
	 * Truncate to a character length.
	 *
	 * @param string $value  Value.
	 * @param int    $start  Start (always 0).
	 * @param int    $length Max characters.
	 * @return string
	 */
	private static function cut( string $value, int $start, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, $start, $length, 'UTF-8' ) : substr( $value, $start, $length );
	}

	/**
	 * Lowercase, without whitespace and ASCII punctuation.
	 *
	 * @param string $name Raw name.
	 * @return string
	 */
	private static function name( string $name ): string {
		return (string) preg_replace( '/[\s!-\/:-@\[-`{-~]+/u', '', Hasher::lowercase( $name ) );
	}
}

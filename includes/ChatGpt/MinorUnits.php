<?php
/**
 * ISO 4217 minor-unit conversion.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\ChatGpt;

/**
 * Converts a decimal amount to an integer in the currency's minor unit
 * (12999 for 129.99 USD), as the ChatGPT Ads API expects.
 *
 * The exponent follows ISO 4217, not how a shop displays prices: HUF has
 * 2 decimals in ISO 4217 even though it is usually shown without any.
 * Checked against the ISO 4217 active codes table on 2026-09-26.
 */
final class MinorUnits {

	/**
	 * Active currencies whose minor unit is not 2.
	 *
	 * @var array<string, int>
	 */
	private const EXPONENTS = [
		'BIF' => 0,
		'CLP' => 0,
		'DJF' => 0,
		'GNF' => 0,
		'ISK' => 0,
		'JPY' => 0,
		'KMF' => 0,
		'KRW' => 0,
		'PYG' => 0,
		'RWF' => 0,
		'UGX' => 0,
		'UYI' => 0,
		'VND' => 0,
		'VUV' => 0,
		'XAF' => 0,
		'XOF' => 0,
		'XPF' => 0,
		'BHD' => 3,
		'IQD' => 3,
		'JOD' => 3,
		'KWD' => 3,
		'LYD' => 3,
		'OMR' => 3,
		'TND' => 3,
		'CLF' => 4,
		'UYW' => 4,
	];

	/**
	 * Number of minor-unit digits for a currency (2 when not listed).
	 *
	 * @param string $currency ISO 4217 code.
	 * @return int
	 */
	public static function exponent( string $currency ): int {
		return self::EXPONENTS[ strtoupper( $currency ) ] ?? 2;
	}

	/**
	 * Convert a decimal amount to minor units.
	 *
	 * @param float  $amount   Decimal amount (e.g. 129.99).
	 * @param string $currency ISO 4217 code.
	 * @return int
	 */
	public static function to_minor( float $amount, string $currency ): int {
		return (int) round( $amount * ( 10 ** self::exponent( $currency ) ) );
	}

	/**
	 * A valid-looking ISO 4217 code in upper case, or ''.
	 *
	 * @param mixed $currency Raw currency.
	 * @return string
	 */
	public static function code( $currency ): string {
		$code = strtoupper( trim( is_scalar( $currency ) ? (string) $currency : '' ) );

		return 1 === preg_match( '/^[A-Z]{3}$/', $code ) ? $code : '';
	}
}

<?php
/**
 * Options management.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel;

/**
 * Plugin options storage.
 */
final class Options {

	public const OPTION_NAME = 'lw_pixel_options';

	/**
	 * Keys holding API secrets: never returned in plaintext by read APIs.
	 */
	public const SECRET_KEYS = [ 'fb_capi_token', 'ga4_mp_api_secret', 'chatgpt_api_key' ];

	/**
	 * Placeholder returned instead of a stored secret. Writing it back
	 * through set-options keeps the stored value.
	 */
	public const SECRET_MASK = '********';

	/**
	 * Cached options array.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $options = null;

	/**
	 * Default option values, sourced from DefaultOptions.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return DefaultOptions::all();
	}

	/**
	 * Get all options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		if ( null === self::$options ) {
			$saved         = get_option( self::OPTION_NAME, [] );
			self::$options = wp_parse_args( $saved, self::get_defaults() );
		}

		return self::$options;
	}

	/**
	 * Get a single option.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$options = self::get_all();

		if ( array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}

		return $default ?? ( self::get_defaults()[ $key ] ?? null );
	}

	/**
	 * Set a single option.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 * @return bool
	 */
	public static function set( string $key, mixed $value ): bool {
		$options         = self::get_all();
		$options[ $key ] = $value;

		return self::save( $options );
	}

	/**
	 * Save the entire options array.
	 *
	 * @param array<string, mixed> $options Options array.
	 * @return bool
	 */
	public static function save( array $options ): bool {
		self::$options = $options;
		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Replace every non-empty secret with SECRET_MASK (empty stays empty,
	 * so callers can still tell whether a secret is set).
	 *
	 * @param array<string, mixed> $options Options.
	 * @return array<string, mixed>
	 */
	public static function mask_secrets( array $options ): array {
		foreach ( self::SECRET_KEYS as $key ) {
			if ( ! empty( $options[ $key ] ) ) {
				$options[ $key ] = self::SECRET_MASK;
			}
		}

		return $options;
	}

	/**
	 * Reset cached options (useful in tests / after writes).
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$options = null;
	}
}

<?php
/**
 * Pixel config CLI command.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CLI;

use LightweightPlugins\Pixel\Admin\SettingsSanitizer;
use LightweightPlugins\Pixel\Options;
use WP_CLI;
use WP_CLI\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage Pixel plugin configuration.
 */
final class ConfigCommand {

	/**
	 * List all configuration values.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * [--changed]
	 * : Only show keys whose value differs from the default.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel config list
	 *     $ wp lw-pixel config list --changed
	 *     $ wp lw-pixel config list --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @subcommand list
	 */
	public function list_config( array $args, array $assoc_args ): void {
		unset( $args );

		$format       = (string) Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$only_changed = (bool) Utils\get_flag_value( $assoc_args, 'changed', false );

		$options  = Options::get_all();
		$defaults = Options::get_defaults();
		$items    = [];

		foreach ( $options as $key => $value ) {
			$default = $defaults[ $key ] ?? null;

			if ( $only_changed && $value === $default ) {
				continue;
			}

			$items[] = [
				'key'        => $key,
				'value'      => self::stringify( $value ),
				'default'    => self::stringify( $default ),
				'is_default' => ( $value === $default ) ? 'yes' : 'no',
			];
		}

		Utils\format_items( $format, $items, [ 'key', 'value', 'default', 'is_default' ] );
	}

	/**
	 * Get a single configuration value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The setting key.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel config get fb_pixel_id
	 *     $ wp lw-pixel config get ga4_measurement_id
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		[ $key ] = $args;

		if ( ! array_key_exists( $key, Options::get_defaults() ) ) {
			WP_CLI::error( "Unknown setting key: '{$key}'" );
		}

		WP_CLI::log( self::stringify( Options::get( $key ) ) );
	}

	/**
	 * Set a configuration value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The setting key.
	 *
	 * <value>
	 * : The setting value. Booleans accept true/false/1/0/yes/no/on/off. Arrays accept comma-separated values.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel config set fb_enabled true
	 *     $ wp lw-pixel config set fb_pixel_id 1234567890
	 *     $ wp lw-pixel config set ga4_measurement_id G-XXXXXXX
	 *     $ wp lw-pixel config set consent_marketing_pixels "fb,tiktok,pinterest"
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function set( array $args, array $assoc_args ): void {
		unset( $assoc_args );

		[ $key, $raw_value ] = $args;

		$defaults = Options::get_defaults();

		if ( ! array_key_exists( $key, $defaults ) ) {
			WP_CLI::error( "Unknown setting key: '{$key}'" );
		}

		$value = self::cast_value( (string) $raw_value, $defaults[ $key ] );

		$current         = Options::get_all();
		$current[ $key ] = $value;
		$sanitized       = SettingsSanitizer::sanitize( $current );

		if ( Options::save( $sanitized ) ) {
			WP_CLI::success( "Set '{$key}' to '" . self::stringify( $sanitized[ $key ] ?? $value ) . "'." );
		} else {
			WP_CLI::error( "Failed to update '{$key}'." );
		}
	}

	/**
	 * Reset all settings to defaults.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel config reset --yes
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function reset( array $args, array $assoc_args ): void {
		unset( $args );

		WP_CLI::confirm( 'Reset all LW Pixel settings to defaults?', $assoc_args );

		if ( Options::save( Options::get_defaults() ) ) {
			WP_CLI::success( 'All settings reset to defaults.' );
		} else {
			WP_CLI::error( 'Failed to reset settings.' );
		}
	}

	/**
	 * Cast a string input to match the default's type.
	 *
	 * @param string $raw_value Raw input.
	 * @param mixed  $type_ref  Default value used for type inference.
	 * @return mixed
	 */
	private static function cast_value( string $raw_value, mixed $type_ref ): mixed {
		if ( is_bool( $type_ref ) ) {
			return in_array( strtolower( $raw_value ), [ 'true', '1', 'yes', 'on' ], true );
		}

		if ( is_int( $type_ref ) ) {
			return (int) $raw_value;
		}

		if ( is_array( $type_ref ) ) {
			$parts = array_map( 'trim', explode( ',', $raw_value ) );
			return array_values( array_filter( $parts, static fn ( string $p ): bool => '' !== $p ) );
		}

		return $raw_value;
	}

	/**
	 * Convert a stored option value into a printable string.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function stringify( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}

		if ( null === $value ) {
			return '';
		}

		return (string) $value;
	}
}

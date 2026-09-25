<?php
/**
 * Settings response shape.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\Settings;

use LightweightPlugins\Pixel\Options;

/**
 * The payload GET and a successful POST share: every option typed like its
 * default (secrets as null — their state is in meta.secrets) plus meta.
 */
final class SettingsResponse {

	/**
	 * Build the payload from storage.
	 *
	 * @return array{options: array<string, mixed>, meta: array<string, mixed>}
	 */
	public static function build(): array {
		return [
			'options' => self::options(),
			'meta'    => SettingsMeta::build(),
		];
	}

	/**
	 * Typed options, secrets withheld.
	 *
	 * @return array<string, mixed>
	 */
	public static function options(): array {
		$stored  = Options::get_all();
		$options = [];

		foreach ( Options::get_defaults() as $key => $default ) {
			if ( in_array( $key, Options::SECRET_KEYS, true ) ) {
				$options[ $key ] = null;
				continue;
			}

			$options[ $key ] = self::typed( $stored[ $key ] ?? $default, $default );
		}

		return $options;
	}

	/**
	 * Cast a stored value to its default's type.
	 *
	 * @param mixed $value   Stored value.
	 * @param mixed $default Default value.
	 * @return mixed
	 */
	private static function typed( mixed $value, mixed $default ): mixed {
		if ( is_bool( $default ) ) {
			return (bool) $value;
		}

		if ( is_int( $default ) ) {
			return (int) $value;
		}

		if ( is_array( $default ) ) {
			return array_values( array_map( 'strval', array_filter( (array) $value, 'is_scalar' ) ) );
		}

		return is_scalar( $value ) ? (string) $value : (string) $default;
	}
}

<?php
/**
 * Settings sanitiser.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin;

use LightweightPlugins\Pixel\Options;

/**
 * Sanitises submitted settings against the defaults schema.
 */
final class SettingsSanitizer {

	/**
	 * Keys that should be stored verbatim (no sanitize_text_field).
	 */
	private const RAW_KEYS = [ 'head_code', 'footer_code', 'body_open_code' ];

	/**
	 * Keys that should be sanitized as textarea (preserve line breaks).
	 */
	private const TEXTAREA_KEYS = [ 'event_thankyou_urls' ];

	/**
	 * Sanitise the submitted values.
	 *
	 * @param array<string, mixed> $input Submitted input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input ): array {
		$defaults  = Options::get_defaults();
		$current   = Options::get_all();
		$sanitized = [];

		foreach ( $defaults as $key => $default ) {
			$fallback          = $current[ $key ] ?? $default;
			$sanitized[ $key ] = self::sanitize_value( $key, $default, $fallback, $input[ $key ] ?? null );
		}

		return $sanitized;
	}

	/**
	 * Sanitise a single value based on its default type.
	 *
	 * @param string $key      Option key.
	 * @param mixed  $default  Default value (also defines the type).
	 * @param mixed  $fallback Current value.
	 * @param mixed  $value    Submitted value (or null when missing).
	 * @return mixed
	 */
	private static function sanitize_value( string $key, mixed $default, mixed $fallback, mixed $value ): mixed {
		if ( is_bool( $default ) ) {
			return ! empty( $value );
		}

		if ( is_int( $default ) ) {
			return null === $value ? $fallback : absint( $value );
		}

		if ( is_array( $default ) ) {
			return is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : (array) $fallback;
		}

		if ( in_array( $key, self::RAW_KEYS, true ) ) {
			return self::sanitize_raw_code( $value, (string) $fallback );
		}

		if ( in_array( $key, self::TEXTAREA_KEYS, true ) ) {
			return null === $value ? $fallback : sanitize_textarea_field( $value );
		}

		return null === $value ? $fallback : sanitize_text_field( $value );
	}

	/**
	 * Sanitise a raw script blob.
	 *
	 * Only users with `unfiltered_html` may submit raw markup. On multisite,
	 * regular admins do not have this cap — silently keep the previous value
	 * to prevent privilege escalation through head/body/footer code injection.
	 *
	 * @param mixed  $value    Submitted value.
	 * @param string $fallback Current stored value.
	 * @return string
	 */
	private static function sanitize_raw_code( mixed $value, string $fallback ): string {
		if ( null === $value ) {
			return $fallback;
		}

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			return $fallback;
		}

		return trim( wp_unslash( (string) $value ) );
	}
}

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
	 * Identifier keys limited to a safe character set. The ChatGPT Ads pixel
	 * ID format is not documented, so only characters that are safe in a URL
	 * query value are kept — no pattern is assumed.
	 */
	private const IDENTIFIER_KEYS = [ 'chatgpt_pixel_id' ];

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

		// A masked secret sent back by a read API means "keep the stored one".
		if ( in_array( $key, Options::SECRET_KEYS, true ) && Options::SECRET_MASK === $value ) {
			return $fallback;
		}

		if ( in_array( $key, self::IDENTIFIER_KEYS, true ) ) {
			return null === $value ? $fallback : (string) preg_replace( '/[^A-Za-z0-9_.:-]/', '', sanitize_text_field( (string) $value ) );
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
	 * Whether a raw code key would be refused for the current user (who
	 * lacks `unfiltered_html`), so callers can report it instead of
	 * silently keeping the old value.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function raw_code_refused( string $key ): bool {
		return in_array( $key, self::RAW_KEYS, true ) && ! current_user_can( 'unfiltered_html' );
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

		// No wp_unslash(): options.php already unslashes before update_option(),
		// and the CLI / ability / migrator inputs were never slashed. A second
		// unslash stripped real backslashes (e.g. /\d+/ in a script) on every save.
		return trim( (string) $value );
	}
}

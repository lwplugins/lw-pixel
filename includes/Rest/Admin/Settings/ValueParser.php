<?php
/**
 * Parses one submitted setting against its rule.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\Settings;

/**
 * Strict per-type parsing. Each method returns [ value, error ]: the
 * normalised value to store, or a translated message (value null).
 * Lists are stored the way the runtime and the CLI read them: comma
 * separated strings, one-per-line text, arrays of pixel IDs.
 */
final class ValueParser {

	/**
	 * Most items a comma list may hold.
	 */
	private const MAX_ITEMS = 20;

	/**
	 * Parse a value.
	 *
	 * @param array<string, mixed> $rule      Rule from FieldSchema.
	 * @param mixed                $value     Submitted value.
	 * @param array<int, string>   $pixel_ids Registered pixel IDs (pixel_list rule).
	 * @return array{0: mixed, 1: string|null}
	 */
	public static function parse( array $rule, mixed $value, array $pixel_ids = [] ): array {
		switch ( $rule['type'] ) {
			case 'bool':
				return self::bool( $value );
			case 'id':
				return self::id( $rule, $value );
			case 'int_list':
				return self::int_list( (int) $rule['min'], (int) $rule['max'], $value );
			case 'word_list':
				return self::word_list( (string) $rule['pattern'], $value );
			case 'lines':
				return self::lines( (int) $rule['max_lines'], (int) $rule['max_line'], $value );
			case 'enum':
				return in_array( $value, (array) $rule['values'], true )
					? [ $value, null ]
					: [ null, __( 'Choose one of the listed options.', 'lw-pixel' ) ];
			case 'pixel_list':
				return self::pixel_list( $value, $pixel_ids );
			case 'raw':
				return self::raw( $value );
			default:
				return self::text( (int) ( $rule['max'] ?? 200 ), $value );
		}
	}

	/**
	 * Strict boolean: true/false, 1/0 or "1"/"0".
	 *
	 * @param mixed $value Submitted value.
	 * @return array{0: bool|null, 1: string|null}
	 */
	public static function bool( mixed $value ): array {
		if ( is_bool( $value ) ) {
			return [ $value, null ];
		}

		if ( 1 === $value || '1' === $value ) {
			return [ true, null ];
		}

		if ( 0 === $value || '0' === $value ) {
			return [ false, null ];
		}

		return [ null, __( 'Must be true or false.', 'lw-pixel' ) ];
	}

	/**
	 * A tracking ID: empty clears it, otherwise it must match the pattern.
	 *
	 * @param array<string, mixed> $rule  Rule.
	 * @param mixed                $value Submitted value.
	 * @return array{0: string|null, 1: string|null}
	 */
	private static function id( array $rule, mixed $value ): array {
		if ( ! is_string( $value ) ) {
			return [ null, __( 'Must be text.', 'lw-pixel' ) ];
		}

		$value = trim( $value );

		if ( ! empty( $rule['uppercase'] ) ) {
			$value = strtoupper( $value );
		} elseif ( ! empty( $rule['lowercase'] ) ) {
			$value = strtolower( $value );
		}

		if ( '' === $value || 1 === preg_match( (string) $rule['pattern'], $value ) ) {
			return [ $value, null ];
		}

		return [ null, __( 'This ID does not have the expected format. Copy it again from the ad platform.', 'lw-pixel' ) ];
	}

	/**
	 * Comma list of whole numbers within a range, stored as "25,50,75".
	 *
	 * @param int   $min   Smallest item.
	 * @param int   $max   Largest item.
	 * @param mixed $value Submitted value (string or array).
	 * @return array{0: string|null, 1: string|null}
	 */
	private static function int_list( int $min, int $max, mixed $value ): array {
		$items = self::items( $value );

		if ( null === $items ) {
			return [ null, __( 'Must be a comma-separated list.', 'lw-pixel' ) ];
		}

		$numbers = [];

		foreach ( $items as $item ) {
			if ( 1 !== preg_match( '/^\d{1,6}$/', $item ) || (int) $item < $min || (int) $item > $max ) {
				return [
					null,
					/* translators: 1: smallest allowed number, 2: largest allowed number. */
					sprintf( __( 'Use whole numbers from %1$d to %2$d, separated by commas.', 'lw-pixel' ), $min, $max ),
				];
			}

			$numbers[ (int) $item ] = (int) $item;
		}

		ksort( $numbers );

		return [ implode( ',', $numbers ), null ];
	}

	/**
	 * Comma list of lower-case words, stored as "pdf,zip".
	 *
	 * @param string $pattern Item pattern.
	 * @param mixed  $value   Submitted value (string or array).
	 * @return array{0: string|null, 1: string|null}
	 */
	private static function word_list( string $pattern, mixed $value ): array {
		$items = self::items( $value );

		if ( null === $items ) {
			return [ null, __( 'Must be a comma-separated list.', 'lw-pixel' ) ];
		}

		$words = [];

		foreach ( $items as $item ) {
			$item = strtolower( ltrim( $item, '.' ) );

			if ( 1 !== preg_match( $pattern, $item ) ) {
				return [ null, __( 'Use file extensions such as pdf or zip (letters and digits only), separated by commas.', 'lw-pixel' ) ];
			}

			$words[ $item ] = $item;
		}

		return [ implode( ',', $words ), null ];
	}

	/**
	 * Split a comma list into trimmed, non-empty items (capped).
	 *
	 * @param mixed $value String or array.
	 * @return array<int, string>|null Null when not a list or too long.
	 */
	private static function items( mixed $value ): ?array {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		if ( ! is_array( $value ) ) {
			return null;
		}

		$items = [];

		foreach ( $value as $item ) {
			if ( ! is_scalar( $item ) ) {
				return null;
			}

			$item = trim( (string) $item );

			if ( '' !== $item ) {
				$items[] = $item;
			}
		}

		return count( $items ) > self::MAX_ITEMS ? null : $items;
	}

	/**
	 * One entry per line (URL fragments), stored newline-separated.
	 *
	 * @param int   $max_lines Most lines.
	 * @param int   $max_line  Longest line.
	 * @param mixed $value     Submitted value.
	 * @return array{0: string|null, 1: string|null}
	 */
	private static function lines( int $max_lines, int $max_line, mixed $value ): array {
		if ( ! is_string( $value ) ) {
			return [ null, __( 'Must be text.', 'lw-pixel' ) ];
		}

		$split = preg_split( '/\R/', $value );
		$lines = array_values( array_filter( array_map( 'trim', false === $split ? [] : $split ), static fn ( string $line ): bool => '' !== $line ) );

		if ( count( $lines ) > $max_lines ) {
			/* translators: %d: maximum number of lines. */
			return [ null, sprintf( __( 'Use at most %d lines.', 'lw-pixel' ), $max_lines ) ];
		}

		foreach ( $lines as $line ) {
			if ( mb_strlen( $line ) > $max_line ) {
				/* translators: %d: maximum number of characters. */
				return [ null, sprintf( __( 'Each line can be at most %d characters long.', 'lw-pixel' ), $max_line ) ];
			}
		}

		return [ sanitize_textarea_field( implode( "\n", $lines ) ), null ];
	}

	/**
	 * Pixel IDs for a consent category.
	 *
	 * @param mixed              $value     Submitted value.
	 * @param array<int, string> $pixel_ids Registered pixel IDs.
	 * @return array{0: array<int, string>|null, 1: string|null}
	 */
	private static function pixel_list( mixed $value, array $pixel_ids ): array {
		if ( ! is_array( $value ) ) {
			return [ null, __( 'Must be a list of pixel IDs.', 'lw-pixel' ) ];
		}

		$ids = [];

		foreach ( $value as $id ) {
			if ( ! is_string( $id ) || ! in_array( $id, $pixel_ids, true ) ) {
				/* translators: %s: pixel ID. */
				return [ null, sprintf( __( 'Unknown pixel: "%s".', 'lw-pixel' ), is_scalar( $id ) ? substr( (string) $id, 0, 40 ) : '?' ) ];
			}

			$ids[ $id ] = $id;
		}

		return [ array_values( $ids ), null ];
	}

	/**
	 * Custom code, kept byte for byte (the capability check is the caller's).
	 *
	 * @param mixed $value Submitted value.
	 * @return array{0: string|null, 1: string|null}
	 */
	private static function raw( mixed $value ): array {
		if ( ! is_string( $value ) ) {
			return [ null, __( 'Must be text.', 'lw-pixel' ) ];
		}

		if ( strlen( $value ) > FieldSchema::MAX_CODE_BYTES ) {
			/* translators: %d: maximum size in kilobytes. */
			return [ null, sprintf( __( 'The code can be at most %d KB.', 'lw-pixel' ), (int) ( FieldSchema::MAX_CODE_BYTES / 1024 ) ) ];
		}

		return [ trim( $value ), null ];
	}

	/**
	 * Plain one-line text.
	 *
	 * @param int   $max   Longest value.
	 * @param mixed $value Submitted value.
	 * @return array{0: string|null, 1: string|null}
	 */
	private static function text( int $max, mixed $value ): array {
		if ( ! is_string( $value ) ) {
			return [ null, __( 'Must be text.', 'lw-pixel' ) ];
		}

		$value = sanitize_text_field( $value );

		if ( mb_strlen( $value ) > $max ) {
			/* translators: %d: maximum number of characters. */
			return [ null, sprintf( __( 'Use at most %d characters.', 'lw-pixel' ), $max ) ];
		}

		return [ $value, null ];
	}
}

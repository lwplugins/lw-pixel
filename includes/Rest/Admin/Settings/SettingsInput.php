<?php
/**
 * Validates a settings save request.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\Settings;

/**
 * Checks a whole partial body ({ option_key: value }) before anything is
 * written. Keys that are not sent are never touched. A secret sent as null
 * keeps the stored one, "" clears it, any other string replaces it.
 */
final class SettingsInput {

	/**
	 * Longest accepted secret.
	 */
	private const MAX_SECRET = 512;

	/**
	 * Field errors: key => messages.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $errors = [];

	/**
	 * Validated changes.
	 *
	 * @var array<string, mixed>
	 */
	private array $values = [];

	/**
	 * Validate a body.
	 *
	 * @param array<array-key, mixed> $body      Decoded JSON body.
	 * @param array<string, mixed>    $current   Current options (for cross-field checks).
	 * @param array<int, string>      $pixel_ids Registered pixel IDs.
	 * @param array<string, string>   $locked    Keys pinned by a constant => constant.
	 * @param bool                    $can_code  Whether the user may write custom code.
	 */
	public function __construct( array $body, array $current, array $pixel_ids, array $locked, bool $can_code ) {
		foreach ( $body as $key => $value ) {
			$key  = (string) $key;
			$rule = FieldSchema::rule( $key );

			if ( null === $rule ) {
				$this->errors[ $key ][] = __( 'Unknown setting.', 'lw-pixel' );
			} elseif ( 'secret' === $rule['type'] ) {
				$this->parse_secret( $key, $value, $locked );
			} elseif ( 'raw' === $rule['type'] && ! $can_code ) {
				$this->errors[ $key ][] = __( 'Only users who may post unfiltered HTML can change custom code.', 'lw-pixel' );
			} else {
				$this->store( $key, ValueParser::parse( $rule, $value, $pixel_ids ) );
			}
		}

		$this->check_consent_overlap( $current );
	}

	/**
	 * Field errors (empty when the body is valid).
	 *
	 * @return array<string, array<int, string>>
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 * Validated changes, keyed by option.
	 *
	 * @return array<string, mixed>
	 */
	public function values(): array {
		return $this->errors ? [] : $this->values;
	}

	/**
	 * Keep a parse result.
	 *
	 * @param string                          $key    Option key.
	 * @param array{0: mixed, 1: string|null} $result Parse result.
	 * @return void
	 */
	private function store( string $key, array $result ): void {
		if ( null !== $result[1] ) {
			$this->errors[ $key ][] = $result[1];
			return;
		}

		$this->values[ $key ] = $result[0];
	}

	/**
	 * Validate a write-only secret.
	 *
	 * @param string                $key    Option key.
	 * @param mixed                 $value  Submitted value.
	 * @param array<string, string> $locked Keys pinned by a constant.
	 * @return void
	 */
	private function parse_secret( string $key, mixed $value, array $locked ): void {
		if ( null === $value ) {
			return;
		}

		if ( isset( $locked[ $key ] ) ) {
			/* translators: %s: PHP constant name. */
			$this->errors[ $key ][] = sprintf( __( 'This key is set in wp-config.php (%s), so it cannot be changed here.', 'lw-pixel' ), $locked[ $key ] );
			return;
		}

		if ( ! is_string( $value ) ) {
			$this->errors[ $key ][] = __( 'Must be text.', 'lw-pixel' );
			return;
		}

		$value = trim( $value );

		if ( strlen( $value ) > self::MAX_SECRET || 1 === preg_match( '/[^\x21-\x7E]/', $value ) ) {
			$this->errors[ $key ][] = __( 'Paste the key exactly as shown, without spaces.', 'lw-pixel' );
			return;
		}

		$this->values[ $key ] = $value;
	}

	/**
	 * A pixel may sit in one consent category only (the last list read
	 * would silently win otherwise).
	 *
	 * @param array<string, mixed> $current Current options.
	 * @return void
	 */
	private function check_consent_overlap( array $current ): void {
		$lists = array_keys( FieldSchema::consent_lists() );
		$sent  = array_intersect( $lists, array_keys( $this->values ) );

		if ( [] === $sent ) {
			return;
		}

		$seen = [];

		foreach ( $lists as $list ) {
			$ids = (array) ( $this->values[ $list ] ?? $current[ $list ] ?? [] );

			foreach ( $ids as $id ) {
				$seen[ (string) $id ][] = $list;
			}
		}

		foreach ( $seen as $id => $in ) {
			if ( count( $in ) < 2 ) {
				continue;
			}

			foreach ( array_intersect( $sent, $in ) as $list ) {
				/* translators: %s: pixel ID. */
				$this->errors[ $list ][] = sprintf( __( 'Pixel "%s" is in more than one consent category.', 'lw-pixel' ), $id );
			}
		}
	}
}

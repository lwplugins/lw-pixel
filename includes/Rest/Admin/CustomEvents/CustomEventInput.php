<?php
/**
 * Validates a custom event write.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\CustomEvents;

use LightweightPlugins\Pixel\CustomEvents\MetaBoxes;
use LightweightPlugins\Pixel\Rest\Admin\Settings\ValueParser;

/**
 * Checks the submitted fields over the stored record (create: the
 * defaults). Only submitted fields are validated one by one, so an older
 * event can be switched on or off even if it predates a rule; the rules that
 * tie fields together (a click needs a selector) run on the merged record
 * whenever one of those fields is sent.
 */
final class CustomEventInput {

	public const TRIGGERS = [ 'page_load', 'click', 'scroll', 'time' ];

	/**
	 * Event name: letters, digits and underscores, starting with a letter,
	 * at most 40 characters — accepted by every connected ad platform.
	 */
	private const NAME_PATTERN = '/^[A-Za-z][A-Za-z0-9_]{0,39}$/';

	/**
	 * Field errors.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $errors = [];

	/**
	 * Merged meta record.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Title and status changes.
	 *
	 * @var array<string, mixed>
	 */
	private array $post = [];

	/**
	 * Validate.
	 *
	 * @param array<array-key, mixed>   $body     Submitted fields.
	 * @param array<string, mixed>|null $existing Stored record, or null on create.
	 */
	public function __construct( array $body, ?array $existing ) {
		$this->data = $existing ?? MetaBoxes::defaults();

		foreach ( $body as $key => $value ) {
			$this->field( (string) $key, $value );
		}

		if ( null === $existing && '' === $this->data['event_name'] && ! isset( $this->errors['event_name'] ) ) {
			$this->errors['event_name'][] = __( 'Give the event a name.', 'lw-pixel' );
		}

		$touches = array_intersect( [ 'trigger_type', 'selector' ], array_keys( $body ) );

		if ( ( null === $existing || [] !== $touches ) && 'click' === $this->data['trigger_type'] && '' === $this->data['selector'] && ! isset( $this->errors['selector'] ) ) {
			$this->errors['selector'][] = __( 'A click trigger needs a CSS selector.', 'lw-pixel' );
		}
	}

	/**
	 * Field errors.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 * Merged meta record.
	 *
	 * @return array<string, mixed>
	 */
	public function data(): array {
		return $this->data;
	}

	/**
	 * Title / enabled changes ({ title?, enabled? }).
	 *
	 * @return array<string, mixed>
	 */
	public function post(): array {
		return $this->post;
	}

	/**
	 * Validate one field.
	 *
	 * @param string $key   Field.
	 * @param mixed  $value Value.
	 * @return void
	 */
	private function field( string $key, mixed $value ): void {
		$result = match ( $key ) {
			'title'        => $this->text( $value, 200, '/^.*$/su' ),
			'enabled'      => ValueParser::bool( $value ),
			'fire_once'    => ValueParser::bool( $value ),
			'event_name'   => $this->text( $value, 40, self::NAME_PATTERN, __( 'Start with a letter, then use letters, digits or underscores (max. 40), e.g. MyCustomEvent.', 'lw-pixel' ) ),
			'trigger_type' => in_array( $value, self::TRIGGERS, true ) ? [ $value, null ] : [ null, __( 'Choose one of the listed options.', 'lw-pixel' ) ],
			'selector'     => $this->text( $value, 500, '/^[^{}<\x00-\x1F]*$/u', __( 'This is not a usable CSS selector.', 'lw-pixel' ) ),
			'scroll_pct'   => $this->int( $value, 1, 100 ),
			'time_seconds' => $this->int( $value, 1, 86400 ),
			'page_pattern' => $this->text( $value, 500, '#^(?:[/*]\S*)?$#u', __( 'Start with / (a path such as /products/*) or leave it empty for every page.', 'lw-pixel' ) ),
			'value'        => $this->text( $value, 12, '/^(?:\d{1,9}(?:\.\d{1,4})?)?$/', __( 'Use a number such as 10 or 9.99, or leave it empty.', 'lw-pixel' ) ),
			'currency'     => $this->text( is_string( $value ) ? strtoupper( $value ) : $value, 3, '/^[A-Z]{3}$/', __( 'Use a three-letter currency code such as HUF, EUR or USD.', 'lw-pixel' ) ),
			default        => [ null, __( 'Unknown field.', 'lw-pixel' ) ],
		};

		if ( null !== $result[1] ) {
			$this->errors[ $key ][] = $result[1];
		} elseif ( 'title' === $key || 'enabled' === $key ) {
			$this->post[ $key ] = $result[0];
		} else {
			$this->data[ $key ] = $result[0];
		}
	}

	/**
	 * Trimmed text within a length and pattern.
	 *
	 * @param mixed  $value   Value.
	 * @param int    $max     Longest value.
	 * @param string $pattern Full-match pattern.
	 * @param string $message Message when the pattern fails.
	 * @return array{0: string|null, 1: string|null}
	 */
	private function text( mixed $value, int $max, string $pattern, string $message = '' ): array {
		if ( ! is_string( $value ) ) {
			return [ null, __( 'Must be text.', 'lw-pixel' ) ];
		}

		$value = trim( $value );

		if ( mb_strlen( $value ) > $max ) {
			/* translators: %d: maximum number of characters. */
			return [ null, sprintf( __( 'Use at most %d characters.', 'lw-pixel' ), $max ) ];
		}

		if ( 1 !== preg_match( $pattern, $value ) ) {
			return [ null, $message ];
		}

		return [ sanitize_text_field( $value ), null ];
	}

	/**
	 * Whole number within a range.
	 *
	 * @param mixed $value Value.
	 * @param int   $min   Smallest.
	 * @param int   $max   Largest.
	 * @return array{0: int|null, 1: string|null}
	 */
	private function int( mixed $value, int $min, int $max ): array {
		if ( is_string( $value ) && 1 === preg_match( '/^\d{1,6}$/', $value ) ) {
			$value = (int) $value;
		}

		if ( ! is_int( $value ) || $value < $min || $value > $max ) {
			/* translators: 1: smallest allowed number, 2: largest allowed number. */
			return [ null, sprintf( __( 'Use a whole number from %1$d to %2$d.', 'lw-pixel' ), $min, $max ) ];
		}

		return [ $value, null ];
	}
}

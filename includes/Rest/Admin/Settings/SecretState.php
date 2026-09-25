<?php
/**
 * What the admin screen may know about a secret.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\Settings;

use LightweightPlugins\Pixel\Options;

/**
 * A secret (access token, API key) never leaves the server: the screen gets
 * whether one is set, where it comes from and a hint (last four characters)
 * so the user can tell which one it is.
 */
final class SecretState {

	public const SOURCE_CONSTANT = 'constant';
	public const SOURCE_OPTION   = 'option';
	public const SOURCE_NONE     = 'none';

	/**
	 * Secrets that a wp-config.php constant can pin, and the constant. Only
	 * list a key here when the runtime really reads the constant.
	 */
	private const CONSTANTS = [
		'chatgpt_api_key' => 'LW_PIXEL_CHATGPT_API_KEY',
	];

	/**
	 * Secrets shorter than this get no tail in the hint: four characters of
	 * a short secret would give away too much of it.
	 */
	private const MIN_TAIL_LENGTH = 16;

	/**
	 * State of every secret key.
	 *
	 * @param array<string, mixed> $stored Stored options.
	 * @return array<string, array{set: bool, source: string, hint: string}>
	 */
	public static function all( array $stored ): array {
		$states = [];

		foreach ( Options::SECRET_KEYS as $key ) {
			$states[ $key ] = self::describe( self::constant_value( $key ), (string) ( $stored[ $key ] ?? '' ) );
		}

		return $states;
	}

	/**
	 * Keys pinned by a defined, non-empty constant, with the constant name.
	 *
	 * @return array<string, string>
	 */
	public static function locked(): array {
		$locked = [];

		foreach ( self::CONSTANTS as $key => $constant ) {
			if ( null !== self::constant_value( $key ) ) {
				$locked[ $key ] = $constant;
			}
		}

		return $locked;
	}

	/**
	 * Describe the effective secret.
	 *
	 * @param string|null $constant_value Value from the constant, or null.
	 * @param string      $stored_value   Value stored in the options.
	 * @return array{set: bool, source: string, hint: string}
	 */
	public static function describe( ?string $constant_value, string $stored_value ): array {
		if ( null !== $constant_value && '' !== $constant_value ) {
			return self::shape( self::SOURCE_CONSTANT, trim( $constant_value ) );
		}

		if ( '' !== trim( $stored_value ) ) {
			return self::shape( self::SOURCE_OPTION, trim( $stored_value ) );
		}

		return [
			'set'    => false,
			'source' => self::SOURCE_NONE,
			'hint'   => '',
		];
	}

	/**
	 * Masked form of a secret: "…abcd", or "…" for a short one.
	 *
	 * @param string $secret Secret.
	 * @return string
	 */
	public static function hint( string $secret ): string {
		return '…' . ( strlen( $secret ) >= self::MIN_TAIL_LENGTH ? substr( $secret, -4 ) : '' );
	}

	/**
	 * Non-empty constant value of a key, or null.
	 *
	 * @param string $key Option key.
	 * @return string|null
	 */
	private static function constant_value( string $key ): ?string {
		$constant = self::CONSTANTS[ $key ] ?? '';

		if ( '' === $constant || ! defined( $constant ) ) {
			return null;
		}

		// Same test as the runtime reader (Server\ChatGptCAPI::api_key()).
		$value = (string) constant( $constant );

		return '' !== $value ? $value : null;
	}

	/**
	 * The state of a set secret.
	 *
	 * @param string $source Where it comes from.
	 * @param string $secret The secret.
	 * @return array{set: bool, source: string, hint: string}
	 */
	private static function shape( string $source, string $secret ): array {
		return [
			'set'    => true,
			'source' => $source,
			'hint'   => self::hint( $secret ),
		];
	}
}

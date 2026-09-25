<?php
/**
 * Base case for the admin REST tests: options live in an in-memory array.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Rest\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

// WordPress REST doubles (WordPress is not loaded in unit tests).
foreach ( [ 'WP_Error', 'WP_REST_Server' ] as $lw_pixel_class ) {
	if ( ! class_exists( $lw_pixel_class ) ) {
		require_once dirname( __DIR__, 3 ) . '/Stubs/' . $lw_pixel_class . '.php';
	}
}
if ( ! class_exists( 'WP_REST_Request' ) ) {
	require_once dirname( __DIR__, 3 ) . '/Stubs/WP_REST.php';
}
require_once __DIR__ . '/lw-pixel-stub.php';

/**
 * Stubs get_option()/update_option() over $this->store, plus the helpers the
 * REST layer calls. The current user may do everything unless a test says
 * otherwise ($this->caps).
 */
abstract class RestTestCase extends MonkeyTestCase {

	/**
	 * Option name => value.
	 *
	 * @var array<string, mixed>
	 */
	protected array $store = [];

	/**
	 * Capability => granted.
	 *
	 * @var array<string, bool>
	 */
	protected array $caps = [
		'manage_options'  => true,
		'unfiltered_html' => true,
	];

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();

		Functions\when( 'get_option' )->alias(
			fn ( $name, $fallback = false ) => array_key_exists( $name, $this->store ) ? $this->store[ $name ] : $fallback
		);
		Functions\when( 'update_option' )->alias(
			function ( $name, $value ): bool {
				$this->store[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'current_user_can' )->alias( fn ( $cap ): bool => ! empty( $this->caps[ $cap ] ) );
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'sanitize_text_field' )->alias( static fn ( $value ): string => trim( (string) $value ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn ( $value ): string => (string) $value );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Stored plugin options.
	 *
	 * @return array<string, mixed>
	 */
	protected function saved(): array {
		return (array) ( $this->store[ Options::OPTION_NAME ] ?? [] );
	}
}

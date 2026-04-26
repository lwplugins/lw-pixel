<?php
/**
 * Minimal WordPress function stubs for the standalone smoke test.
 *
 * Only stubs the functions the lw-pixel core touches without WP loaded.
 * Side-effects (hooks, options) live in static arrays so assertions can
 * inspect them.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

$GLOBALS['lw_pixel_options']   = [];
$GLOBALS['lw_pixel_transients'] = [];
$GLOBALS['lw_pixel_actions']    = [];
$GLOBALS['lw_pixel_filters']    = [];

if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, $callback, int $priority = 10, int $arity = 1 ): bool {
		$GLOBALS['lw_pixel_actions'][ $hook ][] = [ $callback, $priority, $arity ];
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, $callback, int $priority = 10, int $arity = 1 ): bool {
		$GLOBALS['lw_pixel_filters'][ $hook ][] = [ $callback, $priority, $arity ];
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $hook, $value, ...$args ) {
		$callbacks = $GLOBALS['lw_pixel_filters'][ $hook ] ?? [];
		foreach ( $callbacks as [$callback]) {
			$value = $callback( $value, ...$args );
		}
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( string $hook, ...$args ): void {
		$callbacks = $GLOBALS['lw_pixel_actions'][ $hook ] ?? [];
		foreach ( $callbacks as [$callback]) {
			$callback( ...$args );
		}
	}
}

if ( ! function_exists( 'has_filter' ) ) {
	function has_filter( string $hook ): bool {
		return ! empty( $GLOBALS['lw_pixel_filters'][ $hook ] );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $key, $default = false ) {
		return $GLOBALS['lw_pixel_options'][ $key ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $key, $value ): bool {
		$GLOBALS['lw_pixel_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	function add_option( string $key, $value ): bool {
		if ( ! isset( $GLOBALS['lw_pixel_options'][ $key ] ) ) {
			$GLOBALS['lw_pixel_options'][ $key ] = $value;
		}
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $key ): bool {
		unset( $GLOBALS['lw_pixel_options'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $key ) {
		return $GLOBALS['lw_pixel_transients'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $key, $value, int $ttl = 0 ): bool {
		$GLOBALS['lw_pixel_transients'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $key ): bool {
		unset( $GLOBALS['lw_pixel_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ): array {
		return array_merge( (array) $defaults, (array) $args );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin(): bool {
		return false;
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $type = '' ): bool {
		return false;
	}
}

if ( ! function_exists( 'is_search' ) ) {
	function is_search(): bool {
		return false;
	}
}

if ( ! function_exists( 'get_search_query' ) ) {
	function get_search_query(): string {
		return '';
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post() {
		return null;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return $url;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $text ): string {
		return trim( strip_tags( $text ) );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( string $path = '/' ): string {
		return 'http://example.test' . $path;
	}
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
	function wp_doing_cron(): bool {
		return false;
	}
}

if ( ! function_exists( 'wp_get_document_title' ) ) {
	function wp_get_document_title(): string {
		return 'Test page';
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id(): int {
		return 0;
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return null;
	}
}

if ( ! class_exists( 'WP_Query' ) ) {
	class WP_Query { // phpcs:ignore Generic.Classes.OpeningBraceSameLine, WordPress.NamingConventions.ValidClassName
		/** @var array<int, mixed> */
		public array $posts = [];

		/**
		 * @param array<string, mixed> $args Args.
		 */
		public function __construct( array $args = [] ) {
			unset( $args );
		}
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post { // phpcs:ignore Generic.Classes.OpeningBraceSameLine, WordPress.NamingConventions.ValidClassName
		public int $ID = 0; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	}
}

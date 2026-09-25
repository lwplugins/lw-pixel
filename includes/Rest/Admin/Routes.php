<?php
/**
 * Admin REST routes bootstrap.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin;

use LightweightPlugins\Pixel\Rest\Admin\CustomEvents\CustomEventsController;
use WP_Error;

/**
 * Registers the lw-pixel/v1/admin/* routes used by the React admin.
 *
 * Every route needs manage_options; REST cookie auth supplies the nonce
 * (X-WP-Nonce), so write routes need no extra nonce of their own.
 */
final class Routes {

	/**
	 * REST namespace.
	 */
	public const NAMESPACE = 'lw-pixel/v1';

	/**
	 * Hook the route registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
	}

	/**
	 * Register every admin route.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		( new SettingsController() )->register_routes();
		( new CustomEventsController() )->register_routes();
		( new MigratorController() )->register_routes();
		( new SystemReportController() )->register_routes();
		( new ConnectionController() )->register_routes();
	}

	/**
	 * Permission callback: only administrators manage tracking.
	 *
	 * @return bool
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Register one route with a handler per HTTP method.
	 *
	 * @param string                $path     Route path under the namespace.
	 * @param array<string, string> $handlers Method constant => public method name on $owner.
	 * @param object                $owner    Controller instance.
	 * @return void
	 */
	public static function add( string $path, array $handlers, object $owner ): void {
		$endpoints = [];

		foreach ( $handlers as $methods => $callback ) {
			$endpoints[] = [
				'methods'             => $methods,
				'callback'            => [ $owner, $callback ],
				'permission_callback' => [ self::class, 'can_manage' ],
			];
		}

		register_rest_route( self::NAMESPACE, $path, $endpoints );
	}

	/**
	 * A translated REST error.
	 *
	 * @param string               $code    Error code.
	 * @param string               $message Translated message.
	 * @param int                  $status  HTTP status.
	 * @param array<string, mixed> $data    Extra error data.
	 * @return WP_Error
	 */
	public static function error( string $code, string $message, int $status, array $data = [] ): WP_Error {
		return new WP_Error( $code, $message, array_merge( [ 'status' => $status ], $data ) );
	}

	/**
	 * The "request too large" error, or null when the body fits.
	 *
	 * @param string $body     Raw request body.
	 * @param int    $max_size Largest accepted size in bytes.
	 * @return WP_Error|null
	 */
	public static function too_large( string $body, int $max_size ): ?WP_Error {
		if ( strlen( $body ) <= $max_size ) {
			return null;
		}

		return self::error( 'lw_pixel_too_large', __( 'The request is too large.', 'lw-pixel' ), 413 );
	}

	/**
	 * Decoded JSON body as an array (empty for an empty or invalid body).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return array<array-key, mixed>
	 */
	public static function body( \WP_REST_Request $request ): array {
		// Null for an empty or undecodable body at runtime, despite the stub.
		$body = $request->get_json_params();

		return empty( $body ) ? [] : $body;
	}
}

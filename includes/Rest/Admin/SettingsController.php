<?php
/**
 * Settings REST controller.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin;

use LightweightPlugins\Pixel\Admin\SettingsSanitizer;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Rest\Admin\Settings\SecretState;
use LightweightPlugins\Pixel\Rest\Admin\Settings\SettingsInput;
use LightweightPlugins\Pixel\Rest\Admin\Settings\SettingsMeta;
use LightweightPlugins\Pixel\Rest\Admin\Settings\SettingsResponse;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET/POST lw-pixel/v1/admin/settings.
 */
final class SettingsController {

	/**
	 * Largest accepted request body, in bytes (three custom code blobs fit).
	 */
	public const MAX_BYTES = 262144;

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		Routes::add(
			'/admin/settings',
			[
				WP_REST_Server::READABLE  => 'get_settings',
				WP_REST_Server::CREATABLE => 'save_settings',
			],
			$this
		);
	}

	/**
	 * Current state.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response( SettingsResponse::build() );
	}

	/**
	 * Partial, atomic update: only the submitted keys change (a switch that
	 * is not sent is never turned off), and nothing is saved when any of
	 * them is invalid.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_settings( WP_REST_Request $request ) {
		$too_large = Routes::too_large( (string) $request->get_body(), self::MAX_BYTES );

		if ( null !== $too_large ) {
			return $too_large;
		}

		$current = Options::get_all();
		$input   = new SettingsInput(
			Routes::body( $request ),
			$current,
			SettingsMeta::pixel_ids(),
			SecretState::locked(),
			current_user_can( 'unfiltered_html' )
		);

		if ( [] !== $input->errors() ) {
			return Routes::error(
				'lw_pixel_invalid',
				__( 'Some settings are not valid. Nothing was saved.', 'lw-pixel' ),
				400,
				[ 'fields' => $input->errors() ]
			);
		}

		if ( [] !== $input->values() ) {
			// Merged over the stored options, so the sanitizer (shared with
			// the CLI and the abilities) sees every key: missing booleans
			// would otherwise read as "off".
			Options::save( SettingsSanitizer::sanitize( array_merge( $current, $input->values() ) ) );
		}

		return new WP_REST_Response( SettingsResponse::build() );
	}
}

<?php
/**
 * Context the settings screen builds its controls from.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\Settings;

use LightweightPlugins\Pixel\Admin\Integrations;
use LightweightPlugins\Pixel\DefaultOptions;
use LightweightPlugins\Pixel\Options;

use function LightweightPlugins\Pixel\lw_pixel;

/**
 * Registered pixels and their status, detected integrations, secret
 * states, locked keys, enums and what the current user may edit.
 */
final class SettingsMeta {

	/**
	 * Plugin documentation.
	 */
	public const DOCS_URL = 'https://github.com/lwplugins/lw-pixel#readme';

	/**
	 * Build the meta block.
	 *
	 * @return array<string, mixed>
	 */
	public static function build(): array {
		$integrations = Integrations::detect();

		return [
			'pixels'              => self::pixels(),
			'secrets'             => (object) SecretState::all( Options::get_all() ),
			'locked'              => (object) SecretState::locked(),
			'defaults'            => (object) DefaultOptions::all(),
			'integrations'        => (object) $integrations,
			'forms_detected'      => (object) Integrations::forms(),
			'woocommerce_active'  => ! empty( $integrations['woocommerce'] ),
			'lw_cookie_active'    => ! empty( $integrations['lw_cookie'] ),
			'can_unfiltered_html' => current_user_can( 'unfiltered_html' ),
			'enums'               => (object) [
				'compliance_ldu_mode' => [ 'auto', 'force_california' ],
			],
			'consent_categories'  => array_values( FieldSchema::consent_lists() ),
			'max_code_bytes'      => FieldSchema::MAX_CODE_BYTES,
			'docs_url'            => self::DOCS_URL,
		];
	}

	/**
	 * Every registered pixel with its saved status and default consent
	 * category ('' = not gated unless a list names it).
	 *
	 * @return array<int, array{id: string, label: string, enabled: bool, configured: bool, default_category: string}>
	 */
	public static function pixels(): array {
		$defaults = self::default_categories();
		$pixels   = [];

		foreach ( lw_pixel()->get_pixel_manager()->all() as $pixel ) {
			$id       = $pixel->get_id();
			$pixels[] = [
				'id'               => $id,
				'label'            => $pixel->get_label(),
				'enabled'          => $pixel->is_enabled(),
				'configured'       => $pixel->is_configured(),
				'default_category' => $defaults[ $id ] ?? '',
			];
		}

		return $pixels;
	}

	/**
	 * Registered pixel IDs.
	 *
	 * @return array<int, string>
	 */
	public static function pixel_ids(): array {
		return array_map( 'strval', array_keys( lw_pixel()->get_pixel_manager()->all() ) );
	}

	/**
	 * Default category per pixel ID, from the default consent lists (a later
	 * list wins, as in Consent\Manager).
	 *
	 * @return array<string, string>
	 */
	private static function default_categories(): array {
		$defaults = DefaultOptions::all();
		$map      = [];

		foreach ( FieldSchema::consent_lists() as $list => $category ) {
			foreach ( (array) ( $defaults[ $list ] ?? [] ) as $id ) {
				$map[ (string) $id ] = $category;
			}
		}

		return $map;
	}
}

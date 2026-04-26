<?php
/**
 * LW Site Manager integration.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\SiteManager;

/**
 * Hooks into LW Site Manager to register pixel abilities.
 */
final class Integration {

	/**
	 * Register hooks. Safe to call even when Site Manager is not active.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'lw_site_manager_register_categories', [ self::class, 'register_category' ] );
		add_action( 'lw_site_manager_register_abilities', [ self::class, 'register_abilities' ] );
	}

	/**
	 * Register the Pixel ability category.
	 *
	 * @return void
	 */
	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'pixel',
			[
				'label'       => __( 'Tracking Pixels', 'lw-pixel' ),
				'description' => __( 'Tracking pixel management abilities.', 'lw-pixel' ),
			]
		);
	}

	/**
	 * Register pixel abilities.
	 *
	 * @param object $permissions Permission manager from Site Manager.
	 * @return void
	 */
	public static function register_abilities( object $permissions ): void {
		PixelAbilities::register( $permissions );
	}
}

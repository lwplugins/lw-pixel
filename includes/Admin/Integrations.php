<?php
/**
 * Detects the plugins LW Pixel integrates with.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin;

/**
 * Which integration targets are active, by their constants or classes.
 */
final class Integrations {

	/**
	 * Form option key => integration key.
	 */
	public const FORMS = [
		'form_cf7'          => 'cf7',
		'form_wpforms'      => 'wpforms',
		'form_elementor'    => 'elementor_pro',
		'form_gravityforms' => 'gravity_forms',
		'form_forminator'   => 'forminator',
		'form_formidable'   => 'formidable',
		'form_ninjaforms'   => 'ninja_forms',
		'form_fluentforms'  => 'fluent_forms',
		'form_wsform'       => 'ws_form',
	];

	/**
	 * Detected integrations.
	 *
	 * @return array<string, bool>
	 */
	public static function detect(): array {
		return [
			'woocommerce'     => class_exists( '\\WooCommerce' ),
			'lw_cookie'       => defined( 'LW_COOKIE_VERSION' ),
			'lw_site_manager' => defined( 'LW_SITE_MANAGER_VERSION' ),
			'cf7'             => defined( 'WPCF7_VERSION' ),
			'wpforms'         => defined( 'WPFORMS_VERSION' ),
			'elementor_pro'   => defined( 'ELEMENTOR_PRO_VERSION' ),
			'forminator'      => defined( 'FORMINATOR_VERSION' ),
			'formidable'      => class_exists( '\\FrmAppController' ),
			'ninja_forms'     => class_exists( '\\Ninja_Forms' ),
			'fluent_forms'    => defined( 'FLUENTFORM_VERSION' ),
			'ws_form'         => class_exists( '\\WS_Form' ),
			'gravity_forms'   => class_exists( '\\GFForms' ),
		];
	}

	/**
	 * Whether each form integration's plugin is active, by form option key.
	 *
	 * @return array<string, bool>
	 */
	public static function forms(): array {
		$detected = self::detect();
		$forms    = [];

		foreach ( self::FORMS as $option => $integration ) {
			$forms[ $option ] = ! empty( $detected[ $integration ] );
		}

		return $forms;
	}
}

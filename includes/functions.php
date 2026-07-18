<?php
/**
 * Helper functions.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel;

if ( ! function_exists( __NAMESPACE__ . '\\is_woocommerce_active' ) ) {
	/**
	 * Check whether WooCommerce is active.
	 *
	 * @return bool
	 */
	function is_woocommerce_active(): bool {
		return class_exists( '\\WooCommerce' );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\get_pixel_manager' ) ) {
	/**
	 * Get the pixel manager from the main plugin instance.
	 *
	 * @return Pixels\PixelManager
	 */
	function get_pixel_manager(): Pixels\PixelManager {
		$plugin = lw_pixel();
		return $plugin->get_pixel_manager();
	}
}

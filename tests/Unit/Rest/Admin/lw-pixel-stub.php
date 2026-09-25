<?php
/**
 * Stand-in for the plugin singleton (lw-pixel.php is not loaded in unit
 * tests): exposes a real PixelManager, which is all the admin REST layer reads.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel;

use LightweightPlugins\Pixel\Pixels\PixelManager;

if ( ! function_exists( __NAMESPACE__ . '\\lw_pixel' ) ) {
	/**
	 * Test double of the main plugin instance.
	 *
	 * @return object
	 */
	function lw_pixel(): object {
		return new class() {
			public function get_pixel_manager(): PixelManager {
				return new PixelManager();
			}
		};
	}
}

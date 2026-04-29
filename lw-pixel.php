<?php
/**
 * Plugin Name:       LW Pixel
 * Plugin URI:        https://github.com/lwplugins/lw-pixel
 * Description:       Lightweight tracking pixel manager — Meta, Google Analytics 4, Google Ads, GTM, TikTok, Pinterest, Bing in one minimal plugin.
 * Version:           1.0.3
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            LW Plugins
 * Author URI:        https://lwplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lw-pixel
 * Domain Path:       /languages
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LW_PIXEL_VERSION', '1.0.3' );
define( 'LW_PIXEL_FILE', __FILE__ );
define( 'LW_PIXEL_PATH', plugin_dir_path( __FILE__ ) );
define( 'LW_PIXEL_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( LW_PIXEL_PATH . 'vendor/autoload.php' ) ) {
	require_once LW_PIXEL_PATH . 'vendor/autoload.php';
} elseif ( ! class_exists( Plugin::class ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p><strong>LW Pixel:</strong> %s</p></div>',
				esc_html__( 'Autoloader not found. Please run "composer install" in the plugin directory, or re-install the plugin from a release ZIP.', 'lw-pixel' )
			);
		}
	);
	return;
}

register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Activator::class, 'deactivate' ] );

/**
 * Returns the main plugin instance.
 *
 * @return Plugin
 */
function lw_pixel(): Plugin {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Plugin();
	}

	return $instance;
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\lw_pixel' );

CLI\Loader::register();

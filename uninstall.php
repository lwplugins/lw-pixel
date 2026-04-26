<?php
/**
 * Uninstall handler.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'lw_pixel_options' );
delete_option( 'lw_pixel_version' );

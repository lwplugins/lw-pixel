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

/**
 * Remove the plugin's options and the personal data it stored for one site:
 * the per-order checkout context (IP, user agent, browser identifiers) in
 * both order storages (posts and HPOS), and the short-lived server event
 * batches waiting for Action Scheduler.
 *
 * @return void
 */
function lw_pixel_uninstall_site(): void {
	global $wpdb;

	delete_option( 'lw_pixel_options' );
	delete_option( 'lw_pixel_version' );

	$meta_key = '_lw_pixel_capi_context';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off cleanup on uninstall.
	$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => $meta_key ] );

	$hpos_meta = $wpdb->prefix . 'wc_orders_meta';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off cleanup on uninstall.
	if ( $hpos_meta === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $hpos_meta ) ) ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off cleanup on uninstall.
		$wpdb->delete( $hpos_meta, [ 'meta_key' => $meta_key ] );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off cleanup on uninstall.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_lw_pixel_srv_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_lw_pixel_srv_' ) . '%'
		)
	);
}

if ( is_multisite() ) {
	$lw_pixel_sites = get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	);
	foreach ( $lw_pixel_sites as $lw_pixel_site_id ) {
		switch_to_blog( (int) $lw_pixel_site_id );
		lw_pixel_uninstall_site();
		restore_current_blog();
	}
} else {
	lw_pixel_uninstall_site();
}

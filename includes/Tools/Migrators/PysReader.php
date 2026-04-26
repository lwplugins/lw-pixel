<?php
/**
 * Reads stored PixelYourSite options from either of the two locations
 * PYS uses: its own `wp_pys_options` table (newer installs) or the
 * legacy `wp_options` row (older installs).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tools\Migrators;

/**
 * Resolves PYS option buckets across both storage locations.
 */
final class PysReader {

	/**
	 * Read a PYS option bucket as an associative array.
	 *
	 * @param string $bucket PYS bucket key (`pys_facebook`, `pys_ga`, …).
	 * @return array<string, mixed>
	 */
	public static function read( string $bucket ): array {
		$from_table = self::read_from_table( $bucket );
		if ( [] !== $from_table ) {
			return $from_table;
		}

		$legacy = get_option( $bucket, [] );

		return is_array( $legacy ) ? $legacy : [];
	}

	/**
	 * Read from PYS's custom table (`{$prefix}pys_options`).
	 *
	 * @param string $bucket PYS bucket key.
	 * @return array<string, mixed>
	 */
	private static function read_from_table( string $bucket ): array {
		global $wpdb;

		if ( ! $wpdb || ! self::table_exists() ) {
			return [];
		}

		$table = $wpdb->prefix . 'pys_options';
		$row   = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT option_value FROM {$table} WHERE option_name = %s LIMIT 1", $bucket ) // phpcs:ignore WordPress.DB
		);

		if ( null === $row || '' === $row ) {
			return [];
		}

		$decoded = maybe_unserialize( $row );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Whether the PYS options table exists.
	 *
	 * @return bool
	 */
	private static function table_exists(): bool {
		global $wpdb;

		if ( ! $wpdb ) {
			return false;
		}

		$table = $wpdb->prefix . 'pys_options';
		$found = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);

		return $found === $table;
	}

	/**
	 * Whether PYS Free or Pro is currently active on the site.
	 *
	 * @return bool
	 */
	public static function is_pys_active(): bool {
		return defined( 'PYS_FREE_VERSION' ) || defined( 'PYS_VERSION' );
	}
}

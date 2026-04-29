<?php
/**
 * Pixel migrate CLI command.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CLI;

use LightweightPlugins\Pixel\Tools\MigratorRegistry;
use WP_CLI;
use WP_CLI\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Run LW Pixel migrators (PixelYourSite, etc.) from the command line.
 */
final class MigrateCommand {

	/**
	 * List available migrators.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel migrate list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @subcommand list
	 */
	public function list_migrators( array $args, array $assoc_args ): void {
		unset( $args );

		$format = (string) Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$items  = [];

		foreach ( MigratorRegistry::all() as $id => $migrator ) {
			$items[] = [
				'id'        => $id,
				'label'     => $migrator->get_label(),
				'available' => $migrator->is_available() ? 'yes' : 'no',
			];
		}

		if ( [] === $items ) {
			WP_CLI::warning( 'No migrators registered.' );
			return;
		}

		Utils\format_items( $format, $items, [ 'id', 'label', 'available' ] );
	}

	/**
	 * Preview the changes a migrator would apply.
	 *
	 * ## OPTIONS
	 *
	 * <migrator>
	 * : Migrator id (e.g. "pys").
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel migrate preview pys
	 *     $ wp lw-pixel migrate preview pys --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function preview( array $args, array $assoc_args ): void {
		[ $id ]   = $args;
		$format   = (string) Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$migrator = self::resolve( $id );

		$diff = $migrator->preview();

		if ( [] === $diff ) {
			WP_CLI::log( 'No changes — nothing to migrate.' );
			return;
		}

		$items = [];
		foreach ( $diff as $key => $change ) {
			$items[] = [
				'key'  => $key,
				'from' => self::stringify( $change['from'] ?? null ),
				'to'   => self::stringify( $change['to'] ?? null ),
			];
		}

		Utils\format_items( $format, $items, [ 'key', 'from', 'to' ] );
	}

	/**
	 * Execute a migrator.
	 *
	 * ## OPTIONS
	 *
	 * <migrator>
	 * : Migrator id (e.g. "pys").
	 *
	 * [--yes]
	 * : Skip confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel migrate run pys --yes
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function run( array $args, array $assoc_args ): void {
		[ $id ]   = $args;
		$migrator = self::resolve( $id );

		WP_CLI::confirm( "Run the '{$id}' migrator? Existing LW Pixel options will be overwritten where mappings apply.", $assoc_args );

		$result  = $migrator->run();
		$updated = (array) ( $result['updated'] ?? [] );
		$skipped = (array) ( $result['skipped'] ?? [] );

		WP_CLI::success(
			sprintf(
				'Migration "%s" finished — %d updated, %d skipped.',
				$id,
				count( $updated ),
				count( $skipped )
			)
		);

		if ( [] !== $updated ) {
			WP_CLI::log( 'Updated: ' . implode( ', ', $updated ) );
		}

		if ( [] !== $skipped ) {
			WP_CLI::log( 'Skipped: ' . implode( ', ', $skipped ) );
		}
	}

	/**
	 * Resolve a migrator id to an instance, or fail.
	 *
	 * @param string $id Migrator id.
	 * @return \LightweightPlugins\Pixel\Tools\Migrators\MigratorInterface
	 */
	private static function resolve( string $id ) {
		$migrator = MigratorRegistry::get( $id );

		if ( null === $migrator ) {
			WP_CLI::error( "Unknown migrator: '{$id}'. Run 'wp lw-pixel migrate list' to see available ids." );
		}

		if ( ! $migrator->is_available() ) {
			WP_CLI::error( "Migrator '{$id}' is not available on this site (source plugin / data not found)." );
		}

		return $migrator;
	}

	/**
	 * Convert any value into a printable string.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function stringify( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}

		if ( null === $value ) {
			return '';
		}

		return (string) $value;
	}
}

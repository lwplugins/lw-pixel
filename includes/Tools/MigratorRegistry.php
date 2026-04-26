<?php
/**
 * Migrator registry.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tools;

use LightweightPlugins\Pixel\Tools\Migrators\MigratorInterface;
use LightweightPlugins\Pixel\Tools\Migrators\PysMigrator;

/**
 * Builds the list of available migrators.
 */
final class MigratorRegistry {

	/**
	 * All registered migrators.
	 *
	 * @return array<string, MigratorInterface>
	 */
	public static function all(): array {
		$migrators = [
			'pys' => new PysMigrator(),
		];

		/**
		 * Register additional migrators.
		 *
		 * @param array<string, MigratorInterface> $migrators Migrator map.
		 */
		return (array) apply_filters( 'lw_pixel_migrators', $migrators );
	}

	/**
	 * Resolve a migrator by id.
	 *
	 * @param string $id Migrator id.
	 * @return MigratorInterface|null
	 */
	public static function get( string $id ): ?MigratorInterface {
		$all = self::all();
		return $all[ $id ] ?? null;
	}
}

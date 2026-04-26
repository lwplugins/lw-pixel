<?php
/**
 * Migrates PixelYourSite (Free + Pro) settings into lw-pixel.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tools\Migrators;

use LightweightPlugins\Pixel\Options;

/**
 * PixelYourSite migrator.
 */
final class PysMigrator implements MigratorInterface {

	public function get_id(): string {
		return 'pys';
	}

	public function get_label(): string {
		return __( 'PixelYourSite (Free / Pro)', 'lw-pixel' );
	}

	public function is_available(): bool {
		if ( PysReader::is_pys_active() ) {
			return true;
		}

		// Even if PYS is no longer active, its options may still be in the DB.
		foreach ( array_keys( PysMapping::all() ) as $bucket ) {
			if ( [] !== PysReader::read( $bucket ) ) {
				return true;
			}
		}

		return false;
	}

	public function preview(): array {
		$preview = [];
		$current = Options::get_all();

		foreach ( PysMapping::all() as $bucket => $field_map ) {
			$source = PysReader::read( $bucket );

			foreach ( $field_map as $pys_key => $lw_key ) {
				if ( ! array_key_exists( $pys_key, $source ) ) {
					continue;
				}

				$preview[ $lw_key ] = [
					'from' => $current[ $lw_key ] ?? null,
					'to'   => self::cast( $lw_key, $source[ $pys_key ] ),
				];
			}
		}

		return $preview;
	}

	public function run(): array {
		$updated = [];
		$skipped = [];
		$options = Options::get_all();

		foreach ( PysMapping::all() as $bucket => $field_map ) {
			$source = PysReader::read( $bucket );

			foreach ( $field_map as $pys_key => $lw_key ) {
				if ( ! array_key_exists( $pys_key, $source ) ) {
					$skipped[] = "{$bucket}.{$pys_key}";
					continue;
				}

				$options[ $lw_key ] = self::cast( $lw_key, $source[ $pys_key ] );
				$updated[]          = $lw_key;
			}
		}

		Options::save( $options );

		return [
			'updated' => array_values( array_unique( $updated ) ),
			'skipped' => $skipped,
		];
	}

	/**
	 * Coerce a PYS value to the type expected by the matching lw-pixel option.
	 *
	 * @param string $lw_key lw-pixel option key.
	 * @param mixed  $value  Raw PYS value.
	 * @return mixed
	 */
	private static function cast( string $lw_key, mixed $value ): mixed {
		$default = Options::get_defaults()[ $lw_key ] ?? null;

		if ( is_bool( $default ) ) {
			return self::to_bool( $value );
		}

		if ( is_int( $default ) ) {
			return (int) $value;
		}

		if ( is_array( $default ) ) {
			return is_array( $value ) ? array_map( 'strval', $value ) : (array) $default;
		}

		return is_scalar( $value ) ? trim( (string) $value ) : (string) $default;
	}

	/**
	 * Loose boolean cast: handles "1", "true", "yes" alongside booleans.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function to_bool( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) ) {
			return 1 === $value;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), [ '1', 'true', 'yes', 'on' ], true );
		}

		return (bool) $value;
	}
}

<?php
/**
 * WP-CLI command loader.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CLI;

/**
 * Registers the LW Pixel WP-CLI commands when running under WP-CLI.
 */
final class Loader {

	/**
	 * Register all CLI commands.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! \WP_CLI ) {
			return;
		}

		\WP_CLI::add_command( 'lw-pixel', PixelCommand::class );
		\WP_CLI::add_command( 'lw-pixel config', ConfigCommand::class );
		\WP_CLI::add_command( 'lw-pixel migrate', MigrateCommand::class );
	}
}

<?php
/**
 * Migration runner — handles the GET-based "Run migration" link from the
 * Tools tab on the settings page.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tools;

/**
 * Detects the migration trigger query string, verifies the nonce + capability,
 * runs the migrator, and redirects back with a flash banner.
 */
final class MigrationRunner {

	/**
	 * Register the admin_init hook.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_init', [ self::class, 'maybe_handle' ] );
	}

	/**
	 * Build a nonce-protected URL the Tools tab uses for "Run migration".
	 *
	 * @param string $migrator_id Migrator slug.
	 * @return string
	 */
	public static function build_url( string $migrator_id ): string {
		return wp_nonce_url(
			add_query_arg(
				[
					'page'            => 'lw-pixel',
					'lw_pixel_action' => 'migrate',
					'migrator'        => $migrator_id,
				],
				admin_url( 'admin.php' )
			) . '#tools',
			'lw_pixel_migrate_' . $migrator_id
		);
	}

	/**
	 * Handle the migration request when the matching query string is present.
	 *
	 * @return void
	 */
	public static function maybe_handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- nonce is verified below explicitly.
		if ( empty( $_GET['lw_pixel_action'] ) || 'migrate' !== $_GET['lw_pixel_action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$migrator_id = isset( $_GET['migrator'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['migrator'] ) ) : '';
		$nonce       = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! wp_verify_nonce( $nonce, 'lw_pixel_migrate_' . $migrator_id ) ) {
			return;
		}

		$migrator = MigratorRegistry::get( $migrator_id );

		if ( null === $migrator || ! $migrator->is_available() ) {
			self::redirect( [ 'lw_pixel_error' => 'unknown' ] );
		}

		$result = $migrator->run();

		self::redirect(
			[
				'lw_pixel_migrated'    => $migrator_id,
				'lw_pixel_updated_cnt' => count( $result['updated'] ),
			]
		);
	}

	/**
	 * Redirect back to the Tools tab with the given flash params.
	 *
	 * @param array<string, mixed> $extra Query args.
	 * @return void
	 */
	private static function redirect( array $extra ): void {
		$url = add_query_arg(
			array_merge( [ 'page' => 'lw-pixel' ], $extra ),
			admin_url( 'admin.php' )
		) . '#tools';

		wp_safe_redirect( $url );
		exit;
	}
}

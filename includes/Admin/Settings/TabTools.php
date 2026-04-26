<?php
/**
 * Tools settings tab — hosts plugin-level utilities such as the migrator.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

use LightweightPlugins\Pixel\Tools\MigrationRunner;
use LightweightPlugins\Pixel\Tools\MigratorRegistry;
use LightweightPlugins\Pixel\Tools\Migrators\MigratorInterface;

/**
 * Renders the Tools tab on the settings page.
 */
final class TabTools implements TabInterface {

	public function get_slug(): string {
		return 'tools';
	}

	public function get_label(): string {
		return __( 'Tools', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-admin-tools';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Tools', 'lw-pixel' ); ?></h2>
		<?php $this->render_flash(); ?>

		<h3><?php esc_html_e( 'Import settings from another plugin', 'lw-pixel' ); ?></h3>
		<p><?php esc_html_e( 'Bring your existing pixel configuration over to LW Pixel without retyping every ID.', 'lw-pixel' ); ?></p>

		<?php
		foreach ( MigratorRegistry::all() as $migrator ) {
			$this->render_migrator_card( $migrator );
		}
		?>
		<?php
	}

	/**
	 * Render the success / error flash banner from the redirect.
	 *
	 * @return void
	 */
	private function render_flash(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only flash, set by our own redirect.
		if ( isset( $_GET['lw_pixel_migrated'] ) ) {
			$slug  = sanitize_text_field( wp_unslash( (string) $_GET['lw_pixel_migrated'] ) );
			$count = isset( $_GET['lw_pixel_updated_cnt'] ) ? absint( $_GET['lw_pixel_updated_cnt'] ) : 0;
			printf(
				'<div class="notice notice-success lw-notice"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: migrator id, 2: number of updated keys */
						__( 'Migration completed (%1$s). %2$d option(s) updated.', 'lw-pixel' ),
						$slug,
						$count
					)
				)
			);
		}

		if ( isset( $_GET['lw_pixel_error'] ) ) {
			echo '<div class="notice notice-error lw-notice"><p>' . esc_html__( 'Unknown migrator.', 'lw-pixel' ) . '</p></div>';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Render a single migrator card with preview + run link.
	 *
	 * @param MigratorInterface $migrator Migrator.
	 * @return void
	 */
	private function render_migrator_card( MigratorInterface $migrator ): void {
		$available = $migrator->is_available();
		$preview   = $available ? $migrator->preview() : [];
		?>
		<div class="card" style="max-width: 900px; padding: 16px 20px; margin-top: 16px;">
			<h4 style="margin: 0 0 12px;">
				<?php echo esc_html( $migrator->get_label() ); ?>
				<?php if ( ! $available ) : ?>
					<span style="font-size:12px;color:#888;font-weight:400;">— <?php esc_html_e( 'not detected', 'lw-pixel' ); ?></span>
				<?php endif; ?>
			</h4>

			<?php if ( ! $available ) : ?>
				<p class="description">
					<?php esc_html_e( 'Source plugin is not active and no legacy data was found in the database.', 'lw-pixel' ); ?>
				</p>
				<?php return; ?>
			<?php endif; ?>

			<?php ( new MigratorPreviewTable() )->render( $preview ); ?>

			<p style="margin-top: 12px;">
				<a href="<?php echo esc_url( MigrationRunner::build_url( $migrator->get_id() ) ); ?>"
					class="button button-primary"
					onclick="return confirm('<?php echo esc_js( __( 'This will overwrite matching LW Pixel options. Continue?', 'lw-pixel' ) ); ?>');">
					<?php
					/* translators: %s: migrator label */
					echo esc_html( sprintf( __( 'Migrate from %s', 'lw-pixel' ), $migrator->get_label() ) );
					?>
				</a>
				<span class="description" style="margin-left: 12px;">
					<?php
					/* translators: %d: number of migratable keys */
					echo esc_html( sprintf( __( '%d option(s) ready to import.', 'lw-pixel' ), count( $preview ) ) );
					?>
				</span>
			</p>
		</div>
		<?php
	}
}

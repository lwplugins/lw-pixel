<?php
/**
 * System Report tab — diagnostics for support.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

use LightweightPlugins\Pixel\Admin\SystemReport;

/**
 * Renders the diagnostic report tab.
 */
final class TabSystemReport implements TabInterface {

	public function get_slug(): string {
		return 'system-report';
	}

	public function get_label(): string {
		return __( 'System Report', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-info';
	}

	public function render(): void {
		$report = SystemReport::generate();

		?>
		<h2><?php esc_html_e( 'System Report', 'lw-pixel' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Copy this report when filing a support request. Secrets are redacted.', 'lw-pixel' ); ?>
		</p>

		<textarea class="large-text code" rows="20" readonly onclick="this.select();"><?php echo esc_textarea( wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></textarea>

		<h3 style="margin-top:2em;"><?php esc_html_e( 'Pixel status', 'lw-pixel' ); ?></h3>
		<table class="widefat striped" style="max-width: 720px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Pixel', 'lw-pixel' ); ?></th>
					<th><?php esc_html_e( 'Enabled', 'lw-pixel' ); ?></th>
					<th><?php esc_html_e( 'Configured', 'lw-pixel' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $report['pixels'] as $row ) : ?>
				<tr>
					<td><?php echo esc_html( (string) $row['label'] ); ?></td>
					<td><?php echo $row['enabled'] ? '✓' : '—'; ?></td>
					<td><?php echo $row['configured'] ? '✓' : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

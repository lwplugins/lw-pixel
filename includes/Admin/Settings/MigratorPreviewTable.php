<?php
/**
 * Migrator preview-diff table.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the "current value → will become" comparison table for a migrator.
 */
final class MigratorPreviewTable {

	/**
	 * Render the table.
	 *
	 * @param array<string, array{from: mixed, to: mixed}> $preview Preview rows.
	 * @return void
	 */
	public function render( array $preview ): void {
		if ( [] === $preview ) {
			echo '<p class="description">' . esc_html__( 'Nothing to migrate — no matching settings were found.', 'lw-pixel' ) . '</p>';
			return;
		}

		?>
		<table class="widefat striped" style="margin-top: 8px;">
			<thead>
				<tr>
					<th style="width: 35%;"><?php esc_html_e( 'LW Pixel option', 'lw-pixel' ); ?></th>
					<th style="width: 30%;"><?php esc_html_e( 'Current value', 'lw-pixel' ); ?></th>
					<th style="width: 35%;"><?php esc_html_e( 'Will become', 'lw-pixel' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $preview as $key => $row ) : ?>
					<tr>
						<td><code><?php echo esc_html( $key ); ?></code></td>
						<td><?php echo esc_html( self::format( $row['from'] ) ); ?></td>
						<td><strong><?php echo esc_html( self::format( $row['to'] ) ); ?></strong></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Format a single value for display.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function format( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? '✓ true' : '✗ false';
		}

		if ( null === $value ) {
			return '—';
		}

		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}

		return (string) $value;
	}
}

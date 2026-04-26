<?php
/**
 * Compliance settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

use LightweightPlugins\Pixel\Options;

/**
 * Renders the Compliance tab (Medical traffic + LDU).
 */
final class TabCompliance implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'compliance';
	}

	public function get_label(): string {
		return __( 'Compliance', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-shield';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Compliance', 'lw-pixel' ); ?></h2>

		<h3><?php esc_html_e( 'Medical traffic mode', 'lw-pixel' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Strips identifiable parameters (IP, UA, advanced matching, page URL params) from event payloads. Recommended for healthcare / medical sites.', 'lw-pixel' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'compliance_medical',
						'label' => __( 'Treat this site as medical / health traffic', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Limited Data Use (LDU)', 'lw-pixel' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Adds the Meta data_processing_options block to CAPI events for California (CCPA) compliance.', 'lw-pixel' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'compliance_ldu',
						'label' => __( 'Mark events with LDU', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="compliance_ldu_mode"><?php esc_html_e( 'LDU mode', 'lw-pixel' ); ?></label></th>
				<td>
					<?php $this->render_ldu_mode_select(); ?>
					<p class="description"><?php esc_html_e( '"Auto" lets Meta resolve geo. "Force California" sets country=1, state=1000.', 'lw-pixel' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the LDU mode select.
	 *
	 * @return void
	 */
	private function render_ldu_mode_select(): void {
		$current = (string) Options::get( 'compliance_ldu_mode', 'auto' );
		$options = [
			'auto'             => __( 'Auto (Meta resolves geo)', 'lw-pixel' ),
			'force_california' => __( 'Force California', 'lw-pixel' ),
		];

		printf( '<select id="compliance_ldu_mode" name="%s[compliance_ldu_mode]">', esc_attr( Options::OPTION_NAME ) );
		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}
}

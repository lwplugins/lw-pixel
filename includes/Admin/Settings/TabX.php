<?php
/**
 * X (Twitter) settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the X (Twitter) pixel configuration.
 */
final class TabX implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'x';
	}

	public function get_label(): string {
		return __( 'X (Twitter)', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-twitter';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'X (Twitter) Pixel', 'lw-pixel' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'x_enabled',
						'label' => __( 'Enable X (Twitter) Pixel', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="x_pixel_id"><?php esc_html_e( 'Pixel ID', 'lw-pixel' ); ?></label></th>
				<td>
				<?php
				$this->render_text_field(
					[
						'name'        => 'x_pixel_id',
						'placeholder' => 'oXXXX',
						'description' => __( 'Your X Universal Pixel ID.', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
		</table>
		<?php
	}
}

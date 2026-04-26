<?php
/**
 * Snapchat settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the Snapchat pixel configuration.
 */
final class TabSnapchat implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'snapchat';
	}

	public function get_label(): string {
		return __( 'Snapchat', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-camera';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Snapchat Pixel', 'lw-pixel' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'snapchat_enabled',
						'label' => __( 'Enable Snapchat Pixel', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="snapchat_pixel_id"><?php esc_html_e( 'Pixel ID', 'lw-pixel' ); ?></label></th>
				<td>
				<?php
				$this->render_text_field(
					[
						'name'        => 'snapchat_pixel_id',
						'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
						'description' => __( 'Your Snapchat Pixel ID (UUID format).', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
		</table>
		<?php
	}
}

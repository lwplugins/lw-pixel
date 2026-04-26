<?php
/**
 * TikTok settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the TikTok pixel configuration.
 */
final class TabTikTok implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'tiktok';
	}

	public function get_label(): string {
		return __( 'TikTok', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-format-video';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'TikTok Pixel', 'lw-pixel' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'tiktok_enabled',
							'label' => __( 'Enable TikTok Pixel', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="tiktok_pixel_id"><?php esc_html_e( 'Pixel ID', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'tiktok_pixel_id',
							'placeholder' => 'CXXXXXXXXXXXXXX',
							'description' => __( 'Your TikTok Pixel ID (from Events Manager).', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

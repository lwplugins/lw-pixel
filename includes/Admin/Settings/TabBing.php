<?php
/**
 * Bing settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the Microsoft Bing UET tag configuration.
 */
final class TabBing implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'bing';
	}

	public function get_label(): string {
		return __( 'Bing', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-search';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Microsoft Bing UET', 'lw-pixel' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'bing_enabled',
							'label' => __( 'Enable Microsoft Bing UET tag', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="bing_tag_id"><?php esc_html_e( 'UET Tag ID', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'bing_tag_id',
							'placeholder' => '12345678',
							'description' => __( 'Your Bing UET Tag ID.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

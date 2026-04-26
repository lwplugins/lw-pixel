<?php
/**
 * Google Tag Manager settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders Google Tag Manager configuration.
 */
final class TabGTM implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'gtm';
	}

	public function get_label(): string {
		return __( 'Tag Manager', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-tag';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Google Tag Manager', 'lw-pixel' ); ?></h2>
		<p><?php esc_html_e( 'Inject the GTM container snippet and push events to dataLayer.', 'lw-pixel' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'gtm_enabled',
							'label' => __( 'Enable Google Tag Manager', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="gtm_container_id"><?php esc_html_e( 'Container ID', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'gtm_container_id',
							'placeholder' => 'GTM-XXXXXXX',
							'description' => __( 'Your GTM container ID (starts with GTM-).', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'dataLayer Only', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'        => 'gtm_data_layer_only',
							'label'       => __( 'Push to dataLayer only (do not load the GTM script)', 'lw-pixel' ),
							'description' => __( 'Useful when the GTM script is loaded by another plugin.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

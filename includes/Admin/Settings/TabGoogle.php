<?php
/**
 * Google (GA4 + Ads) settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders Google Analytics 4 and Google Ads configuration.
 */
final class TabGoogle implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'google';
	}

	public function get_label(): string {
		return __( 'Google', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-chart-line';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Google Analytics 4', 'lw-pixel' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'ga4_enabled',
							'label' => __( 'Enable Google Analytics 4', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ga4_measurement_id"><?php esc_html_e( 'Measurement ID', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'ga4_measurement_id',
							'placeholder' => 'G-XXXXXXXXXX',
							'description' => __( 'Your GA4 Measurement ID (starts with G-).', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Anonymize IP', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'ga4_anonymize_ip',
							'label' => __( 'Anonymize IP addresses', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Debug Mode', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'ga4_debug',
							'label' => __( 'Enable debug mode (use Realtime + DebugView)', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Measurement Protocol', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'ga4_mp_enabled',
							'label' => __( 'Send server-side events via the GA4 Measurement Protocol', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ga4_mp_api_secret"><?php esc_html_e( 'MP API Secret', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'ga4_mp_api_secret',
							'description' => __( 'Generate in GA4 Admin → Data Streams → Measurement Protocol API secrets.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Google Ads', 'lw-pixel' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'gads_enabled',
							'label' => __( 'Enable Google Ads conversion tracking', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="gads_conversion_id"><?php esc_html_e( 'Conversion ID', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'gads_conversion_id',
							'placeholder' => 'AW-1234567890',
							'description' => __( 'Your Google Ads Conversion ID (starts with AW-).', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="gads_conversion_label"><?php esc_html_e( 'Conversion Label', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'gads_conversion_label',
							'description' => __( 'Used for the Purchase event conversion.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Remarketing', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'gads_remarketing',
							'label' => __( 'Send remarketing data with each event', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

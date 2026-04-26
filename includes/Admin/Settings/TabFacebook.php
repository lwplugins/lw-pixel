<?php
/**
 * Meta (Facebook) settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the Meta Pixel + CAPI configuration.
 */
final class TabFacebook implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'facebook';
	}

	public function get_label(): string {
		return __( 'Meta', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-facebook';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Meta (Facebook) Pixel', 'lw-pixel' ); ?></h2>
		<p><?php esc_html_e( 'Configure your Meta Pixel and Conversion API.', 'lw-pixel' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'fb_enabled',
							'label' => __( 'Enable Meta Pixel', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fb_pixel_id"><?php esc_html_e( 'Pixel ID', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'fb_pixel_id',
							'placeholder' => '1234567890',
							'description' => __( 'Your Meta Pixel ID (numeric).', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Advanced Matching', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'        => 'fb_advanced_matching',
							'label'       => __( 'Enable advanced matching for logged-in users', 'lw-pixel' ),
							'description' => __( 'Sends hashed email/phone to improve match rate.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Conversion API (Server-side)', 'lw-pixel' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable CAPI', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'fb_capi_enabled',
							'label' => __( 'Send events server-side via Conversion API', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fb_capi_token"><?php esc_html_e( 'Access Token', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'fb_capi_token',
							'description' => __( 'Generate in Events Manager → Settings → Conversion API.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fb_test_event_code"><?php esc_html_e( 'Test Event Code', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'fb_test_event_code',
							'placeholder' => 'TEST12345',
							'description' => __( 'Optional. Use to test events in Events Manager → Test Events.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'External ID', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'fb_send_external_id',
							'label' => __( 'Send external_id (logged-in user ID, hashed) for better matching', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Order Enrich', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'        => 'fb_order_enrich',
							'label'       => __( 'Re-send Purchase to CAPI when WooCommerce order completes/processes', 'lw-pixel' ),
							'description' => __( 'Captures the final order value after payment confirmation.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

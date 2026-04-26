<?php
/**
 * Advanced settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Custom head/body/footer scripts and debug toggles.
 */
final class TabAdvanced implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'advanced';
	}

	public function get_label(): string {
		return __( 'Advanced', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-admin-tools';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Advanced', 'lw-pixel' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Disable for admins', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'        => 'disable_for_admins',
							'label'       => __( 'Do not load pixels for users with manage_options capability', 'lw-pixel' ),
							'description' => __( 'Recommended. Prevents your own admin sessions from polluting reports.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Debug mode', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'        => 'debug_mode',
							'label'       => __( 'Log fired events in the browser console', 'lw-pixel' ),
							'description' => __( 'Useful while configuring pixels. Disable in production.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Custom code', 'lw-pixel' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Raw HTML/JS injected on every page. No sanitization is performed — make sure your code is trusted.', 'lw-pixel' ); ?></p>

		<table class="form-table">
			<tr>
				<th scope="row"><label for="head_code"><?php esc_html_e( '<head> code', 'lw-pixel' ); ?></label></th>
				<td>
				<?php
				$this->render_textarea_field(
					[
						'name' => 'head_code',
						'rows' => 6,
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><label for="body_open_code"><?php esc_html_e( 'After <body>', 'lw-pixel' ); ?></label></th>
				<td>
				<?php
				$this->render_textarea_field(
					[
						'name' => 'body_open_code',
						'rows' => 6,
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><label for="footer_code"><?php esc_html_e( 'Before </body>', 'lw-pixel' ); ?></label></th>
				<td>
				<?php
				$this->render_textarea_field(
					[
						'name' => 'footer_code',
						'rows' => 6,
					]
				);
				?>
					</td>
			</tr>
		</table>
		<?php
	}
}

<?php
/**
 * Pinterest settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the Pinterest tag configuration.
 */
final class TabPinterest implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'pinterest';
	}

	public function get_label(): string {
		return __( 'Pinterest', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-pinterest';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Pinterest Tag', 'lw-pixel' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'pinterest_enabled',
							'label' => __( 'Enable Pinterest Tag', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pinterest_tag_id"><?php esc_html_e( 'Tag ID', 'lw-pixel' ); ?></label></th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'pinterest_tag_id',
							'placeholder' => '2612345678901',
							'description' => __( 'Your Pinterest Tag ID.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Enhanced Match', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'pinterest_em_enabled',
							'label' => __( 'Send hashed email for enhanced match', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

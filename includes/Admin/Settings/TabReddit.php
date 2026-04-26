<?php
/**
 * Reddit settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the Reddit pixel configuration.
 */
final class TabReddit implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'reddit';
	}

	public function get_label(): string {
		return __( 'Reddit', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-share';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Reddit Pixel', 'lw-pixel' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'reddit_enabled',
						'label' => __( 'Enable Reddit Pixel', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="reddit_pixel_id"><?php esc_html_e( 'Pixel ID', 'lw-pixel' ); ?></label></th>
				<td>
				<?php
				$this->render_text_field(
					[
						'name'        => 'reddit_pixel_id',
						'placeholder' => 't2_xxxxxxx',
						'description' => __( 'Your Reddit Advertiser ID.', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
		</table>
		<?php
	}
}

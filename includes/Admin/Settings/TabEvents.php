<?php
/**
 * Events settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the generic event toggles + auto-tracked events + form integrations.
 */
final class TabEvents implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'events';
	}

	public function get_label(): string {
		return __( 'Events', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-flag';
	}

	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Events', 'lw-pixel' ); ?></h2>
		<p><?php esc_html_e( 'Choose which standard events should fire.', 'lw-pixel' ); ?></p>

		<table class="form-table">
			<?php
			$builtins = [
				'event_pageview'     => [ __( 'PageView', 'lw-pixel' ), __( 'Fire PageView on every page', 'lw-pixel' ) ],
				'event_view_content' => [ __( 'ViewContent', 'lw-pixel' ), __( 'Fire ViewContent on singular templates (posts, pages, CPTs)', 'lw-pixel' ) ],
				'event_search'       => [ __( 'Search', 'lw-pixel' ), __( 'Fire Search on the search results page', 'lw-pixel' ) ],
				'event_lead'         => [ __( 'Lead', 'lw-pixel' ), __( 'Fire Lead on form submissions', 'lw-pixel' ) ],
			];
			foreach ( $builtins as $key => [ $title, $label ] ) :
				?>
				<tr>
					<th scope="row"><?php echo esc_html( $title ); ?></th>
					<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => $key,
							'label' => $label,
						]
					);
					?>
						</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<?php ( new EventsAutoSection() )->render(); ?>

		<h3><?php esc_html_e( 'Form integrations', 'lw-pixel' ); ?></h3>
		<table class="form-table">
			<?php
			$forms = [
				'form_cf7'          => __( 'Contact Form 7', 'lw-pixel' ),
				'form_wpforms'      => __( 'WPForms', 'lw-pixel' ),
				'form_elementor'    => __( 'Elementor Pro Forms', 'lw-pixel' ),
				'form_gravityforms' => __( 'Gravity Forms', 'lw-pixel' ),
				'form_forminator'   => __( 'Forminator', 'lw-pixel' ),
				'form_formidable'   => __( 'Formidable Forms', 'lw-pixel' ),
				'form_ninjaforms'   => __( 'Ninja Forms', 'lw-pixel' ),
				'form_fluentforms'  => __( 'Fluent Forms', 'lw-pixel' ),
				'form_wsform'       => __( 'WS Form', 'lw-pixel' ),
			];
			foreach ( $forms as $key => $title ) :
				?>
				<tr>
					<th scope="row"><?php echo esc_html( $title ); ?></th>
					<td>
						<?php
						$this->render_checkbox_field(
							[
								'name'  => $key,
								/* translators: %s: form plugin name */
								'label' => sprintf( __( 'Track %s submissions', 'lw-pixel' ), $title ),
							]
						);
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}
}

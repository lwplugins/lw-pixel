<?php
/**
 * Events settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the generic event toggles.
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
			<tr>
				<th scope="row"><?php esc_html_e( 'PageView', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'event_pageview',
							'label' => __( 'Fire PageView on every page', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'ViewContent', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'event_view_content',
							'label' => __( 'Fire ViewContent on singular templates (posts, pages, CPTs)', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Search', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'event_search',
							'label' => __( 'Fire Search on the search results page', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Lead', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'event_lead',
							'label' => __( 'Fire Lead on form submissions', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Auto-tracked events', 'lw-pixel' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Scroll depth', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'event_scroll',
							'label' => __( 'Fire Scroll events at percentage thresholds', 'lw-pixel' ),
						]
					);
					?>
					<br />
					<?php
					$this->render_text_field(
						[
							'name'        => 'event_scroll_thresholds',
							'placeholder' => '25,50,75,100',
							'description' => __( 'Comma-separated percentages.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Time on page', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'event_time_on_page',
							'label' => __( 'Fire TimeOnPage events at second thresholds', 'lw-pixel' ),
						]
					);
					?>
					<br />
					<?php
					$this->render_text_field(
						[
							'name'        => 'event_time_thresholds',
							'placeholder' => '10,30,60,180',
							'description' => __( 'Comma-separated seconds.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Downloads', 'lw-pixel' ); ?></th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'event_download',
							'label' => __( 'Fire Download events on file links', 'lw-pixel' ),
						]
					);
					?>
					<br />
					<?php
					$this->render_text_field(
						[
							'name'        => 'event_download_extensions',
							'placeholder' => 'pdf,doc,zip,mp3',
							'description' => __( 'Comma-separated file extensions to track.', 'lw-pixel' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Login', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'event_login',
						'label' => __( 'Fire Login event when a user logs in', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Signup', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'event_signup',
						'label' => __( 'Fire CompleteRegistration event on user signup', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Comment', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'event_comment',
						'label' => __( 'Fire Comment event on new comment submission', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
		</table>

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

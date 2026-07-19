<?php
/**
 * Auto-tracked events section of the Events tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the Scroll / Time / Download / Login / Signup / Comment toggles.
 */
final class EventsAutoSection {

	use FieldRendererTrait;

	/**
	 * Render the section.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h3><?php esc_html_e( 'Auto-tracked events', 'lw-pixel' ); ?></h3>
		<table class="form-table">
			<?php $this->row_with_threshold( 'event_scroll', 'event_scroll_thresholds', __( 'Scroll depth', 'lw-pixel' ), __( 'Fire Scroll events at percentage thresholds', 'lw-pixel' ), '25,50,75,100', __( 'Comma-separated percentages.', 'lw-pixel' ) ); ?>
			<?php $this->row_with_threshold( 'event_time_on_page', 'event_time_thresholds', __( 'Time on page', 'lw-pixel' ), __( 'Fire TimeOnPage events at second thresholds', 'lw-pixel' ), '10,30,60,180', __( 'Comma-separated seconds.', 'lw-pixel' ) ); ?>
			<?php $this->row_with_threshold( 'event_download', 'event_download_extensions', __( 'Downloads', 'lw-pixel' ), __( 'Fire Download events on file links', 'lw-pixel' ), 'pdf,doc,zip,mp3', __( 'Comma-separated file extensions to track.', 'lw-pixel' ) ); ?>
			<?php $this->simple_row( 'event_login', __( 'Login', 'lw-pixel' ), __( 'Fire Login event when a user logs in', 'lw-pixel' ) ); ?>
			<?php $this->simple_row( 'event_signup', __( 'Signup', 'lw-pixel' ), __( 'Fire CompleteRegistration event on user signup', 'lw-pixel' ) ); ?>
			<?php $this->simple_row( 'event_comment', __( 'Comment', 'lw-pixel' ), __( 'Fire Comment event on new comment submission', 'lw-pixel' ) ); ?>
			<?php $this->simple_row( 'event_click_phone', __( 'Phone clicks', 'lw-pixel' ), __( 'Fire a Contact event when a visitor clicks a tel: link', 'lw-pixel' ) ); ?>
			<?php $this->simple_row( 'event_click_email', __( 'Email clicks', 'lw-pixel' ), __( 'Fire a Contact event when a visitor clicks a mailto: link', 'lw-pixel' ) ); ?>
			<?php $this->thankyou_row(); ?>
		</table>
		<?php
	}

	/**
	 * Render a simple checkbox row.
	 *
	 * @param string $name  Option key.
	 * @param string $title Row title.
	 * @param string $label Checkbox label.
	 * @return void
	 */
	private function simple_row( string $name, string $title, string $label ): void {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $title ); ?></th>
			<td>
			<?php
			$this->render_checkbox_field(
				[
					'name'  => $name,
					'label' => $label,
				]
			);
			?>
				</td>
		</tr>
		<?php
	}

	/**
	 * Render the thank-you page toggle plus its URL fragment list.
	 *
	 * @return void
	 */
	private function thankyou_row(): void {
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Thank-you pages', 'lw-pixel' ); ?></th>
			<td>
			<?php
			$this->render_checkbox_field(
				[
					'name'  => 'event_thankyou',
					'label' => __( 'Fire a Lead event when the URL matches one of the fragments below', 'lw-pixel' ),
				]
			);
			$this->render_textarea_field(
				[
					'name'        => 'event_thankyou_urls',
					'rows'        => 4,
					'description' => __( 'One URL fragment per line, e.g. koszonjuk. Matched case-insensitively against the page address, including any query string.', 'lw-pixel' ),
				]
			);
			?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render a checkbox + threshold input row.
	 *
	 * @param string $toggle_key      Toggle option key.
	 * @param string $threshold_key   Threshold option key.
	 * @param string $title           Row title.
	 * @param string $toggle_label    Checkbox label.
	 * @param string $threshold_hint  Placeholder.
	 * @param string $threshold_desc  Description text.
	 * @return void
	 */
	private function row_with_threshold( string $toggle_key, string $threshold_key, string $title, string $toggle_label, string $threshold_hint, string $threshold_desc ): void {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $title ); ?></th>
			<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => $toggle_key,
						'label' => $toggle_label,
					]
				);
				?>
				<br />
				<?php
				$this->render_text_field(
					[
						'name'        => $threshold_key,
						'placeholder' => $threshold_hint,
						'description' => $threshold_desc,
					]
				);
				?>
			</td>
		</tr>
		<?php
	}
}

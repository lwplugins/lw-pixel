<?php
/**
 * Custom event metabox renderer.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CustomEvents;

/**
 * Renders the form fields for the Custom Event editor.
 */
final class MetaBoxRenderer {

	/**
	 * Render the form fields.
	 *
	 * @param array<string, mixed> $data Stored data.
	 * @return void
	 */
	public static function render( array $data ): void {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="lw_pixel_event_name"><?php esc_html_e( 'Event name', 'lw-pixel' ); ?></label></th>
				<td>
					<input type="text" id="lw_pixel_event_name" name="lw_pixel[event_name]" value="<?php echo esc_attr( (string) $data['event_name'] ); ?>" class="regular-text" placeholder="MyCustomEvent" />
					<p class="description"><?php esc_html_e( 'Generic event name (PascalCase). Each pixel will receive its mapped variant.', 'lw-pixel' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lw_pixel_trigger_type"><?php esc_html_e( 'Trigger', 'lw-pixel' ); ?></label></th>
				<td>
					<?php self::render_trigger_select( (string) $data['trigger_type'] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lw_pixel_selector"><?php esc_html_e( 'CSS selector', 'lw-pixel' ); ?></label></th>
				<td>
					<input type="text" id="lw_pixel_selector" name="lw_pixel[selector]" value="<?php echo esc_attr( (string) $data['selector'] ); ?>" class="regular-text" placeholder=".my-button, #cta" />
					<p class="description"><?php esc_html_e( 'For "Click element" trigger.', 'lw-pixel' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lw_pixel_scroll_pct"><?php esc_html_e( 'Scroll percentage', 'lw-pixel' ); ?></label></th>
				<td>
					<input type="number" id="lw_pixel_scroll_pct" name="lw_pixel[scroll_pct]" value="<?php echo esc_attr( (string) $data['scroll_pct'] ); ?>" min="1" max="100" class="small-text" />
					<p class="description"><?php esc_html_e( 'For "Scroll" trigger (1-100).', 'lw-pixel' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lw_pixel_time_seconds"><?php esc_html_e( 'Time on page (s)', 'lw-pixel' ); ?></label></th>
				<td>
					<input type="number" id="lw_pixel_time_seconds" name="lw_pixel[time_seconds]" value="<?php echo esc_attr( (string) $data['time_seconds'] ); ?>" min="1" class="small-text" />
					<p class="description"><?php esc_html_e( 'For "Time on page" trigger.', 'lw-pixel' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lw_pixel_page_pattern"><?php esc_html_e( 'Page URL pattern', 'lw-pixel' ); ?></label></th>
				<td>
					<input type="text" id="lw_pixel_page_pattern" name="lw_pixel[page_pattern]" value="<?php echo esc_attr( (string) $data['page_pattern'] ); ?>" class="regular-text" placeholder="/products/* or empty for all pages" />
					<p class="description"><?php esc_html_e( 'Where the event should fire. Empty = all pages. Wildcards (*) supported.', 'lw-pixel' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lw_pixel_value"><?php esc_html_e( 'Value', 'lw-pixel' ); ?></label></th>
				<td>
					<input type="text" id="lw_pixel_value" name="lw_pixel[value]" value="<?php echo esc_attr( (string) $data['value'] ); ?>" class="small-text" placeholder="0" />
					<input type="text" id="lw_pixel_currency" name="lw_pixel[currency]" value="<?php echo esc_attr( (string) $data['currency'] ); ?>" class="small-text" placeholder="USD" maxlength="3" style="margin-left: 8px; width: 70px;" />
					<p class="description"><?php esc_html_e( 'Optional event value + ISO currency code.', 'lw-pixel' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Fire once', 'lw-pixel' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="lw_pixel[fire_once]" value="1" <?php checked( (bool) $data['fire_once'] ); ?> />
						<?php esc_html_e( 'Fire only once per browser session', 'lw-pixel' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render the trigger-type select.
	 *
	 * @param string $current Current selection.
	 * @return void
	 */
	private static function render_trigger_select( string $current ): void {
		$options = [
			'page_load' => __( 'On page load', 'lw-pixel' ),
			'click'     => __( 'Click element (CSS selector)', 'lw-pixel' ),
			'scroll'    => __( 'Scroll percentage', 'lw-pixel' ),
			'time'      => __( 'Time on page', 'lw-pixel' ),
		];

		echo '<select id="lw_pixel_trigger_type" name="lw_pixel[trigger_type]">';
		foreach ( $options as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $current, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}
}

<?php
/**
 * Field Renderer Trait.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

use LightweightPlugins\Pixel\Options;

/**
 * Reusable form-field renderers for the settings tabs.
 */
trait FieldRendererTrait {

	/**
	 * Render a text input.
	 *
	 * @param array{name: string, description?: string, placeholder?: string} $args Field arguments.
	 */
	protected function render_text_field( array $args ): void {
		printf(
			'<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" placeholder="%4$s" class="regular-text" />',
			esc_attr( $args['name'] ),
			esc_attr( Options::OPTION_NAME ),
			esc_attr( (string) Options::get( $args['name'] ) ),
			esc_attr( $args['placeholder'] ?? '' )
		);
		$this->render_description( $args['description'] ?? '' );
	}

	/**
	 * Render a textarea.
	 *
	 * @param array{name: string, description?: string, rows?: int} $args Field arguments.
	 */
	protected function render_textarea_field( array $args ): void {
		printf(
			'<textarea id="%1$s" name="%2$s[%1$s]" rows="%3$d" class="large-text code">%4$s</textarea>',
			esc_attr( $args['name'] ),
			esc_attr( Options::OPTION_NAME ),
			intval( $args['rows'] ?? 5 ),
			esc_textarea( (string) Options::get( $args['name'] ) )
		);
		$this->render_description( $args['description'] ?? '' );
	}

	/**
	 * Render a checkbox.
	 *
	 * @param array{name: string, label: string, description?: string} $args Field arguments.
	 */
	protected function render_checkbox_field( array $args ): void {
		printf(
			'<label><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( $args['name'] ),
			esc_attr( Options::OPTION_NAME ),
			checked( Options::get( $args['name'] ), true, false ),
			esc_html( $args['label'] ?? '' )
		);
		$this->render_description( $args['description'] ?? '' );
	}

	private function render_description( string $desc ): void {
		if ( '' === $desc ) {
			return;
		}
		printf( '<p class="description">%s</p>', esc_html( $desc ) );
	}
}

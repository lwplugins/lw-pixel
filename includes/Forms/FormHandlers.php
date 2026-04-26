<?php
/**
 * Static adapters for each supported form plugin.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Forms;

/**
 * Each method maps a form-plugin hook payload to a normalized
 * [id, name, source] triple used by the Lead event.
 */
final class FormHandlers {

	/**
	 * Contact Form 7.
	 *
	 * @param object $form CF7 form instance.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function cf7( object $form ): array {
		return [
			'id'     => method_exists( $form, 'id' ) ? (int) $form->id() : 0,
			'name'   => method_exists( $form, 'title' ) ? (string) $form->title() : '',
			'source' => 'cf7',
		];
	}

	/**
	 * WPForms.
	 *
	 * @param array $form_data Form data.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function wpforms( array $form_data ): array {
		return [
			'id'     => (int) ( $form_data['id'] ?? 0 ),
			'name'   => (string) ( $form_data['settings']['form_title'] ?? '' ),
			'source' => 'wpforms',
		];
	}

	/**
	 * Elementor Pro Forms.
	 *
	 * @param object $record Submission record.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function elementor( object $record ): array {
		$settings = method_exists( $record, 'get_form_settings' ) ? (array) $record->get_form_settings( '' ) : [];
		return [
			'id'     => (int) ( $settings['id'] ?? 0 ),
			'name'   => (string) ( $settings['form_name'] ?? '' ),
			'source' => 'elementor',
		];
	}

	/**
	 * Forminator.
	 *
	 * @param array $form Form data.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function forminator( array $form ): array {
		return [
			'id'     => (int) ( $form['form_id'] ?? 0 ),
			'name'   => (string) ( $form['form_name'] ?? '' ),
			'source' => 'forminator',
		];
	}

	/**
	 * Formidable Forms.
	 *
	 * @param int $form_id Form id.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function formidable( int $form_id ): array {
		$name = '';
		if ( class_exists( '\\FrmForm' ) ) {
			$form = \FrmForm::getOne( $form_id );
			$name = is_object( $form ) ? (string) ( $form->name ?? '' ) : '';
		}
		return [
			'id'     => $form_id,
			'name'   => $name,
			'source' => 'formidable',
		];
	}

	/**
	 * Ninja Forms.
	 *
	 * @param array $form_data Form data.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function ninjaforms( array $form_data ): array {
		return [
			'id'     => (int) ( $form_data['form_id'] ?? 0 ),
			'name'   => (string) ( $form_data['settings']['title'] ?? '' ),
			'source' => 'ninjaforms',
		];
	}

	/**
	 * Fluent Forms.
	 *
	 * @param object $form Form object.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function fluentforms( object $form ): array {
		return [
			'id'     => (int) ( $form->id ?? 0 ),
			'name'   => (string) ( $form->title ?? '' ),
			'source' => 'fluentforms',
		];
	}

	/**
	 * WS Form.
	 *
	 * @param object $form Form object.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function wsform( object $form ): array {
		return [
			'id'     => (int) ( $form->id ?? 0 ),
			'name'   => (string) ( $form->label ?? '' ),
			'source' => 'wsform',
		];
	}

	/**
	 * Gravity Forms.
	 *
	 * @param array $form Form data.
	 * @return array{id: int, name: string, source: string}
	 */
	public static function gravityforms( array $form ): array {
		return [
			'id'     => (int) ( $form['id'] ?? 0 ),
			'name'   => (string) ( $form['title'] ?? '' ),
			'source' => 'gravityforms',
		];
	}
}

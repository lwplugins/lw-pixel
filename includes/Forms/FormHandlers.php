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
	 * @return array{id: int|string, name: string, source: string}
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
	 * @return array{id: int|string, name: string, source: string}
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
	 * Form_Record::get_form_settings( $key ) returns a single setting (null
	 * when unknown); `id` is the widget's element id, a short hex string.
	 *
	 * @param object $record Submission record.
	 * @return array{id: int|string, name: string, source: string}
	 */
	public static function elementor( object $record ): array {
		$has = method_exists( $record, 'get_form_settings' );

		return [
			'id'     => $has ? (string) $record->get_form_settings( 'id' ) : '',
			'name'   => $has ? (string) $record->get_form_settings( 'form_name' ) : '',
			'source' => 'elementor',
		];
	}

	/**
	 * Forminator.
	 *
	 * The `forminator_form_after_save_entry` hook passes the form id and the
	 * AJAX response, not the form; forms are `forminator_forms` posts.
	 *
	 * @param int $form_id Form id.
	 * @return array{id: int|string, name: string, source: string}
	 */
	public static function forminator( int $form_id ): array {
		return [
			'id'     => $form_id,
			'name'   => $form_id > 0 ? (string) get_post_field( 'post_title', $form_id ) : '',
			'source' => 'forminator',
		];
	}

	/**
	 * Formidable Forms.
	 *
	 * @param int $form_id Form id.
	 * @return array{id: int|string, name: string, source: string}
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
	 * @return array{id: int|string, name: string, source: string}
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
	 * @return array{id: int|string, name: string, source: string}
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
	 * @param object $submit WS_Form_Submit instance.
	 * @return array{id: int|string, name: string, source: string}
	 */
	public static function wsform( object $submit ): array {
		$form = isset( $submit->form_object ) && is_object( $submit->form_object ) ? $submit->form_object : null;

		return [
			'id'     => (int) ( $submit->form_id ?? 0 ),
			'name'   => (string) ( $form->label ?? '' ),
			'source' => 'wsform',
		];
	}

	/**
	 * Gravity Forms.
	 *
	 * @param array $form Form data.
	 * @return array{id: int|string, name: string, source: string}
	 */
	public static function gravityforms( array $form ): array {
		return [
			'id'     => (int) ( $form['id'] ?? 0 ),
			'name'   => (string) ( $form['title'] ?? '' ),
			'source' => 'gravityforms',
		];
	}
}

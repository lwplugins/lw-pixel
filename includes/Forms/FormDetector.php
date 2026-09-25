<?php
/**
 * Form integrations coordinator.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Forms;

use LightweightPlugins\Pixel\Events\Lead;
use LightweightPlugins\Pixel\Events\VisitorEvents;
use LightweightPlugins\Pixel\Options;

/**
 * Hooks into supported form plugins to fire Lead events.
 *
 * Most form plugins submit over AJAX or REST, where no page (and no data
 * island) is rendered, so the Lead is stored in the PendingEventStore and
 * delivered on the visitor's next page view. A classic full-page submission
 * that renders a page in the same request picks it up on that page.
 */
final class FormDetector {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Hook map: option key → [hook name, callback method, callback arity].
	 *
	 * Hook names and arguments checked against the plugins' source:
	 * CF7 6.1.7, WPForms Lite 2.0.2.1, Elementor Pro 4.2.3, Forminator
	 * 1.57.2, Formidable 6.35, Ninja Forms 3.15.4, Fluent Forms 6.2.14,
	 * WS Form Lite 1.12.10, Gravity Forms 2.4.12.
	 *
	 * @return array<string, array{0: string, 1: string, 2: int}>
	 */
	private function hook_map(): array {
		return [
			'form_cf7'          => [ 'wpcf7_mail_sent', 'on_cf7', 1 ],
			'form_wpforms'      => [ 'wpforms_process_complete', 'on_wpforms', 4 ],
			'form_elementor'    => [ 'elementor_pro/forms/new_record', 'on_elementor', 2 ],
			'form_forminator'   => [ 'forminator_form_after_save_entry', 'on_forminator', 1 ],
			'form_formidable'   => [ 'frm_after_create_entry', 'on_formidable', 2 ],
			'form_ninjaforms'   => [ 'ninja_forms_after_submission', 'on_ninjaforms', 1 ],
			'form_fluentforms'  => [ 'fluentform/submission_inserted', 'on_fluentforms', 3 ],
			'form_wsform'       => [ 'wsf_submit_post_complete', 'on_wsform', 1 ],
			'form_gravityforms' => [ 'gform_after_submission', 'on_gravityforms', 2 ],
		];
	}

	/**
	 * Register form-plugin hooks based on enabled options.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		foreach ( $this->hook_map() as $option => [ $hook, $method, $arity ] ) {
			if ( Options::get( $option ) ) {
				add_action( $hook, [ $this, $method ], 10, $arity );
			}
		}
	}

	public function on_cf7( object $form ): void {
		$this->fire( FormHandlers::cf7( $form ) );
	}

	public function on_wpforms( array $fields, array $entry, array $form_data, int $entry_id ): void {
		unset( $fields, $entry, $entry_id );
		$this->fire( FormHandlers::wpforms( $form_data ) );
	}

	public function on_elementor( object $record, object $handler ): void {
		unset( $handler );
		$this->fire( FormHandlers::elementor( $record ) );
	}

	/**
	 * Forminator passes ( $form_id, $response ); the id comes from the
	 * posted data, so it may be a numeric string (or false).
	 *
	 * @param mixed $form_id Form id.
	 * @return void
	 */
	public function on_forminator( $form_id ): void {
		$this->fire( FormHandlers::forminator( is_numeric( $form_id ) ? (int) $form_id : 0 ) );
	}

	public function on_formidable( int $entry_id, int $form_id ): void {
		unset( $entry_id );
		$this->fire( FormHandlers::formidable( $form_id ) );
	}

	public function on_ninjaforms( array $form_data ): void {
		$this->fire( FormHandlers::ninjaforms( $form_data ) );
	}

	public function on_fluentforms( int $entry_id, array $form_data, object $form ): void {
		unset( $entry_id, $form_data );
		$this->fire( FormHandlers::fluentforms( $form ) );
	}

	/**
	 * WS Form fires this for draft saves too; only count real submissions.
	 *
	 * @param object $submit WS_Form_Submit instance.
	 * @return void
	 */
	public function on_wsform( object $submit ): void {
		if ( 'submit' !== ( $submit->post_mode ?? 'submit' ) ) {
			return;
		}

		$this->fire( FormHandlers::wsform( $submit ) );
	}

	public function on_gravityforms( array $entry, array $form ): void {
		unset( $entry );
		$this->fire( FormHandlers::gravityforms( $form ) );
	}

	/**
	 * Store a Lead event from a normalized form descriptor.
	 *
	 * @param array{id: int|string, name: string, source: string} $form Normalized form data.
	 * @return void
	 */
	private function fire( array $form ): void {
		$event = new Lead(
			[
				'form_id'   => (string) $form['id'],
				'form_name' => $form['name'],
				'source'    => $form['source'],
			]
		);

		if ( $event->should_fire() ) {
			VisitorEvents::record( $event->get_name(), $event->get_params() );
		}
	}
}

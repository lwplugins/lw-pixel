<?php
/**
 * Form integrations coordinator.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Forms;

use LightweightPlugins\Pixel\Events\EventManager;
use LightweightPlugins\Pixel\Events\Lead;
use LightweightPlugins\Pixel\Options;

/**
 * Hooks into supported form plugins to fire Lead events.
 */
final class FormDetector {

	/**
	 * Event manager.
	 *
	 * @var EventManager
	 */
	private EventManager $event_manager;

	/**
	 * Constructor.
	 *
	 * @param EventManager $event_manager Event manager.
	 */
	public function __construct( EventManager $event_manager ) {
		$this->event_manager = $event_manager;

		$this->register_hooks();
	}

	/**
	 * Hook map: option key → [hook name, callback method, callback arity].
	 *
	 * @return array<string, array{0: string, 1: string, 2: int}>
	 */
	private function hook_map(): array {
		return [
			'form_cf7'          => [ 'wpcf7_mail_sent', 'on_cf7', 1 ],
			'form_wpforms'      => [ 'wpforms_process_complete', 'on_wpforms', 4 ],
			'form_elementor'    => [ 'elementor_pro/forms/new_record', 'on_elementor', 2 ],
			'form_forminator'   => [ 'forminator_form_after_save_entry', 'on_forminator', 2 ],
			'form_formidable'   => [ 'frm_after_create_entry', 'on_formidable', 2 ],
			'form_ninjaforms'   => [ 'ninja_forms_after_submission', 'on_ninjaforms', 1 ],
			'form_fluentforms'  => [ 'fluentform/submission_inserted', 'on_fluentforms', 3 ],
			'form_wsform'       => [ 'wsf_submit_complete', 'on_wsform', 1 ],
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

	public function on_forminator( int $entry_id, array $form ): void {
		unset( $entry_id );
		$this->fire( FormHandlers::forminator( $form ) );
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

	public function on_wsform( object $form ): void {
		$this->fire( FormHandlers::wsform( $form ) );
	}

	public function on_gravityforms( array $entry, array $form ): void {
		unset( $entry );
		$this->fire( FormHandlers::gravityforms( $form ) );
	}

	/**
	 * Queue a Lead event from a normalized form descriptor.
	 *
	 * @param array{id: int, name: string, source: string} $form Normalized form data.
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
			$this->event_manager->queue( $event->get_name(), $event->get_params() );
		}
	}
}

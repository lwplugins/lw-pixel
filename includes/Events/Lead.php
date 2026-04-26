<?php
/**
 * Lead event (form submissions).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

/**
 * Triggered on form submissions through the form integrations.
 */
final class Lead extends AbstractEvent {

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $params Optional event params (form_id, form_name, value).
	 */
	public function __construct( array $params = [] ) {
		$this->params = array_merge(
			[
				'form_id'   => '',
				'form_name' => '',
				'currency'  => 'USD',
				'value'     => 0,
			],
			$params
		);
	}

	public function get_name(): string {
		return 'Lead';
	}

	protected function option_key(): string {
		return 'event_lead';
	}
}

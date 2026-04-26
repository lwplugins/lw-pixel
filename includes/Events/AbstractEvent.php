<?php
/**
 * Abstract event base class.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

use LightweightPlugins\Pixel\Options;

/**
 * Shared behaviour for all events.
 */
abstract class AbstractEvent implements EventInterface {

	/**
	 * Event-specific parameters (filled in subclass constructors).
	 *
	 * @var array<string, mixed>
	 */
	protected array $params = [];

	/**
	 * Default fire condition: tied to an option toggle.
	 *
	 * @return bool
	 */
	public function should_fire(): bool {
		$key  = $this->option_key();
		$flag = '' === $key ? true : (bool) Options::get( $key, true );

		return (bool) apply_filters( 'lw_pixel_event_should_fire', $flag, $this->get_name() );
	}

	/**
	 * Event params, after filtering.
	 *
	 * @return array<string, mixed>
	 */
	public function get_params(): array {
		return (array) apply_filters( 'lw_pixel_event_params', $this->params, $this->get_name() );
	}

	/**
	 * Plugin option key controlling this event.
	 *
	 * @return string Empty when always-on.
	 */
	abstract protected function option_key(): string;
}

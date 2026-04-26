<?php
/**
 * Search event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

/**
 * Fires on the search results page.
 */
final class Search extends AbstractEvent {

	public function __construct() {
		$this->params = [
			'search_string' => get_search_query(),
		];
	}

	public function get_name(): string {
		return 'Search';
	}

	public function should_fire(): bool {
		return is_search() && parent::should_fire();
	}

	protected function option_key(): string {
		return 'event_search';
	}
}

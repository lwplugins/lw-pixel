<?php
/**
 * PageView event.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

/**
 * Always-on page view event.
 */
final class PageView extends AbstractEvent {

	public function __construct() {
		$this->params = [
			'page_title'    => wp_get_document_title(),
			'page_location' => self::current_url(),
		];
	}

	public function get_name(): string {
		return 'PageView';
	}

	protected function option_key(): string {
		return 'event_pageview';
	}

	/**
	 * Resolve the current request URL safely.
	 *
	 * @return string
	 */
	private static function current_url(): string {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return home_url( '/' );
		}

		return home_url( esc_url_raw( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) );
	}
}

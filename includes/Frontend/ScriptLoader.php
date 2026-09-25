<?php
/**
 * Frontend script loader.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Frontend;

use LightweightPlugins\Pixel\Events\EventManager;
use LightweightPlugins\Pixel\Events\EventRegistry;
use LightweightPlugins\Pixel\Pixels\PixelManager;

/**
 * Enqueues the pixel runtime and prints the inline payload in <head>.
 */
final class ScriptLoader {

	/**
	 * Pixel manager.
	 *
	 * @var PixelManager
	 */
	private PixelManager $pixel_manager;

	/**
	 * Event manager.
	 *
	 * @var EventManager
	 */
	private EventManager $event_manager;

	/**
	 * Constructor.
	 *
	 * @param PixelManager $pixel_manager Pixel manager.
	 * @param EventManager $event_manager Event manager.
	 */
	public function __construct( PixelManager $pixel_manager, EventManager $event_manager ) {
		$this->pixel_manager = $pixel_manager;
		$this->event_manager = $event_manager;

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_head', [ $this, 'print_payload' ], 1 );
		add_action( 'wp_footer', [ $this, 'print_late_payload' ], 5 );
	}

	/**
	 * Enqueue the pixel runtime script.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! $this->pixel_manager->has_any_configured() ) {
			return;
		}

		wp_enqueue_script(
			'lw-pixel',
			LW_PIXEL_URL . 'assets/js/runtime.js',
			[],
			LW_PIXEL_VERSION,
			[
				'in_footer' => false,
				'strategy'  => 'defer',
			]
		);
	}

	/**
	 * Print the inline payload (config + queued events) before the runtime executes.
	 *
	 * @return void
	 */
	public function print_payload(): void {
		if ( ! $this->pixel_manager->has_any_configured() ) {
			return;
		}

		$this->queue_resolved_events();

		$this->print_island( 'lw-pixel-data', $this->event_manager->build_frontend_payload() );
		$this->event_manager->mark_printed();
	}

	/**
	 * Print events queued after wp_head (e.g. the classic-theme Purchase)
	 * into a second data island that the runtime merges on load.
	 *
	 * @return void
	 */
	public function print_late_payload(): void {
		if ( ! did_action( 'wp_head' ) || ! $this->pixel_manager->has_any_configured() ) {
			return;
		}

		$events = $this->event_manager->build_late_events();

		if ( [] !== $events ) {
			$this->print_island( 'lw-pixel-late', [ 'events' => $events ] );
		}

		$this->event_manager->mark_printed();
	}

	/**
	 * Print a JSON data island.
	 *
	 * @param string               $id   Element id.
	 * @param array<string, mixed> $data Payload.
	 * @return void
	 */
	private function print_island( string $id, array $data ): void {
		// JSON_HEX_TAG/AMP/APOS/QUOT prevents an attacker-controlled string (e.g. a
		// post title containing "</script>") from breaking out of the data island.
		$json = (string) wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		printf(
			'<script id="%s" type="application/json">%s</script>' . "\n",
			esc_attr( $id ),
			$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped via JSON_HEX_TAG flags above.
		);
	}

	/**
	 * Queue auto-resolved events (PageView, ViewContent, Search) into the manager.
	 *
	 * @return void
	 */
	private function queue_resolved_events(): void {
		foreach ( EventRegistry::resolve() as $event ) {
			$this->event_manager->queue( $event->get_name(), $event->get_params() );
		}
	}
}

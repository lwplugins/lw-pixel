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

		wp_localize_script(
			'lw-pixel',
			'lwPixelAjax',
			[
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'lw_pixel_ajax' ),
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

		$payload = $this->event_manager->build_frontend_payload();

		// JSON_HEX_TAG/AMP/APOS/QUOT prevents an attacker-controlled string (e.g. a
		// post title containing "</script>") from breaking out of the data island.
		$json = (string) wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		printf(
			'<script id="lw-pixel-data" type="application/json">%s</script>' . "\n",
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

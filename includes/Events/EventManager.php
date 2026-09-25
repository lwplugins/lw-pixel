<?php
/**
 * Event dispatcher / manager.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\Pixels\PixelManager;
use LightweightPlugins\Pixel\Server\EventDispatcher;

/**
 * Bridges queued generic events to provider-specific payloads via PayloadBuilder.
 */
final class EventManager {

	/**
	 * Pixel manager.
	 *
	 * @var PixelManager
	 */
	private PixelManager $pixel_manager;

	/**
	 * Consent manager.
	 *
	 * @var ConsentManager
	 */
	private ConsentManager $consent_manager;

	/**
	 * Pending events keyed by name.
	 *
	 * @var array<int, array{name: string, params: array<string, mixed>, event_id?: string}>
	 */
	private array $queue = [];

	/**
	 * Callbacks to run once the event at the same queue index is printed.
	 *
	 * @var array<int, callable>
	 */
	private array $emit_callbacks = [];

	/**
	 * Number of queue entries already printed into a data island.
	 *
	 * @var int
	 */
	private int $printed = 0;

	/**
	 * Constructor.
	 *
	 * @param PixelManager   $pixel_manager   Pixel manager.
	 * @param ConsentManager $consent_manager Consent manager.
	 */
	public function __construct( PixelManager $pixel_manager, ConsentManager $consent_manager ) {
		$this->pixel_manager   = $pixel_manager;
		$this->consent_manager = $consent_manager;
	}

	/**
	 * Queue an event to be sent during the current request.
	 *
	 * Unless the scope is "browser", the server-side copies are captured
	 * right away (see EventDispatcher); when any was, the browser copy gets
	 * the same event id so the platforms keep only one.
	 *
	 * @param string               $name     Event name.
	 * @param array<string, mixed> $params   Event params.
	 * @param callable|null        $on_emit  Called once the event has actually
	 *                                       been printed into the page (e.g. to
	 *                                       mark an order as tracked).
	 * @param string               $scope    EventDispatcher::SCOPE_* constant.
	 * @param string               $event_id Fixed event id (e.g. an order's).
	 * @return void
	 */
	public function queue( string $name, array $params = [], ?callable $on_emit = null, string $scope = EventDispatcher::SCOPE_PAGE, string $event_id = '' ): void {
		if ( EventDispatcher::SCOPE_BROWSER !== $scope ) {
			$candidate = '' !== $event_id ? $event_id : EventId::generate();
			$event_id  = EventDispatcher::capture( $name, $params, $candidate, $scope ) ? $candidate : $event_id;
		}

		$entry = [
			'name'   => $name,
			'params' => $params,
		];

		// Without a server-side copy, no id is printed: the runtime creates
		// one per visitor, so a cached page never shares an id.
		if ( '' !== $event_id ) {
			$entry['event_id'] = $event_id;
		}

		$this->queue[] = $entry;

		if ( null !== $on_emit ) {
			$this->emit_callbacks[ count( $this->queue ) - 1 ] = $on_emit;
		}
	}

	/**
	 * Build the full payload that the frontend needs.
	 *
	 * @return array<string, mixed>
	 */
	public function build_frontend_payload(): array {
		return ( new PayloadBuilder( $this->pixel_manager, $this->consent_manager ) )->build( $this->queue );
	}

	/**
	 * Resolved events queued after the head payload was built.
	 *
	 * Some events are only known once the page body renders — e.g.
	 * `woocommerce_thankyou` runs inside the classic checkout shortcode,
	 * after `wp_head`. These go into a second data island in the footer.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function build_late_events(): array {
		$late = array_slice( $this->queue, $this->printed );

		if ( [] === $late ) {
			return [];
		}

		return ( new PayloadBuilder( $this->pixel_manager, $this->consent_manager ) )->build_events( $late );
	}

	/**
	 * Record that every event queued so far is now in the page, and run
	 * their emit callbacks.
	 *
	 * @return void
	 */
	public function mark_printed(): void {
		$total = count( $this->queue );

		for ( $i = $this->printed; $i < $total; $i++ ) {
			if ( isset( $this->emit_callbacks[ $i ] ) ) {
				call_user_func( $this->emit_callbacks[ $i ] );
				unset( $this->emit_callbacks[ $i ] );
			}
		}

		$this->printed = $total;
	}

	/**
	 * Currently queued events.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_queue(): array {
		return $this->queue;
	}
}

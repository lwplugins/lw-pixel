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
	 * @var array<int, array{name: string, params: array<string, mixed>}>
	 */
	private array $queue = [];

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
	 * @param string               $name   Event name.
	 * @param array<string, mixed> $params Event params.
	 * @return void
	 */
	public function queue( string $name, array $params = [] ): void {
		$this->queue[] = [
			'name'   => $name,
			'params' => $params,
		];
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
	 * Currently queued events.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_queue(): array {
		return $this->queue;
	}
}

<?php
/**
 * Frontend payload assembler.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\CustomEvents\EventResolver as CustomEventResolver;
use LightweightPlugins\Pixel\Pixels\PixelInterface;
use LightweightPlugins\Pixel\Pixels\PixelManager;

/**
 * Builds the JSON payload printed in the data-island.
 */
final class PayloadBuilder {

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
	 * Build the full payload.
	 *
	 * @param array<int, array{name: string, params: array<string, mixed>}> $queue Queued events.
	 * @return array<string, mixed>
	 */
	public function build( array $queue ): array {
		$pixels     = $this->pixels();
		$active_ids = array_keys( $pixels );

		return [
			'pixels'        => $pixels,
			'events'        => $this->resolve_events( $queue, $active_ids ),
			'consent'       => $this->consent_manager->get_state(),
			'custom_events' => $this->resolve_custom_events( $active_ids ),
			'auto_events'   => $this->resolve_auto_events( $active_ids ),
		];
	}

	/**
	 * Configured pixels with consent applied.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function pixels(): array {
		$pixels = [];

		foreach ( $this->pixel_manager->get_configured() as $pixel ) {
			if ( ! $this->consent_manager->is_pixel_allowed( $pixel->get_id() ) ) {
				continue;
			}

			$pixels[ $pixel->get_id() ] = $pixel->get_frontend_config();
		}

		return $pixels;
	}

	/**
	 * Resolve queued events.
	 *
	 * @param array<int, array{name: string, params: array<string, mixed>}> $queue      Queued events.
	 * @param array<int, string>                                            $active_ids Active pixel ids.
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_events( array $queue, array $active_ids ): array {
		$resolved = [];

		foreach ( $queue as $event ) {
			$mapped = $this->map_for_all( (string) $event['name'], (array) $event['params'], $active_ids );

			if ( [] === $mapped ) {
				continue;
			}

			$resolved[] = [
				'name'   => $event['name'],
				'params' => $event['params'],
				'mapped' => $mapped,
			];
		}

		return $resolved;
	}

	/**
	 * Resolve user-defined custom events.
	 *
	 * @param array<int, string> $active_ids Active pixel ids.
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_custom_events( array $active_ids ): array {
		$descriptors = CustomEventResolver::resolve();
		$resolved    = [];

		foreach ( $descriptors as $descriptor ) {
			$mapped = $this->map_for_all( (string) $descriptor['name'], (array) $descriptor['params'], $active_ids );

			if ( [] !== $mapped ) {
				$descriptor['mapped'] = $mapped;
				$resolved[]           = $descriptor;
			}
		}

		return $resolved;
	}

	/**
	 * Auto-tracked events config + pre-mapped variants.
	 *
	 * @param array<int, string> $active_ids Active pixel ids.
	 * @return array<string, mixed>
	 */
	private function resolve_auto_events( array $active_ids ): array {
		$config = AutoEvents::frontend_config();

		return [
			'scroll'   => array_merge( $config['scroll'], [ 'mapped' => $this->map_for_all( 'Scroll', [], $active_ids ) ] ),
			'time'     => array_merge( $config['time'], [ 'mapped' => $this->map_for_all( 'TimeOnPage', [], $active_ids ) ] ),
			'download' => array_merge( $config['download'], [ 'mapped' => $this->map_for_all( 'Download', [], $active_ids ) ] ),
			'pending'  => $this->resolve_pending( $config['pending'], $active_ids ),
		];
	}

	/**
	 * Resolve pending server-side events.
	 *
	 * @param array<int, array{name: string, params: array<string, mixed>}> $pending    Pending events.
	 * @param array<int, string>                                            $active_ids Active pixel ids.
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_pending( array $pending, array $active_ids ): array {
		$resolved = [];

		foreach ( $pending as $event ) {
			$mapped = $this->map_for_all( (string) $event['name'], (array) $event['params'], $active_ids );

			if ( [] !== $mapped ) {
				$resolved[] = [
					'name'   => $event['name'],
					'params' => $event['params'],
					'mapped' => $mapped,
				];
			}
		}

		return $resolved;
	}

	/**
	 * Map a generic event to every active pixel.
	 *
	 * @param string               $name       Event name.
	 * @param array<string, mixed> $params     Event params.
	 * @param array<int, string>   $active_ids Active pixel ids.
	 * @return array<string, array<string, mixed>>
	 */
	private function map_for_all( string $name, array $params, array $active_ids ): array {
		$mapped = [];

		foreach ( $active_ids as $pixel_id ) {
			$pixel   = $this->pixel_manager->get( $pixel_id );
			$payload = $pixel instanceof PixelInterface ? $pixel->map_event( $name, $params ) : null;

			if ( null !== $payload ) {
				$mapped[ $pixel_id ] = $payload;
			}
		}

		return $mapped;
	}
}

<?php
/**
 * Google Tag Manager provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Outputs the GTM container snippet and pushes events to dataLayer.
 */
final class GoogleTagManager extends AbstractPixel {

	public function get_id(): string {
		return 'gtm';
	}

	public function get_label(): string {
		return __( 'Google Tag Manager', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'gtm_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'container_id', '' );
	}

	public function get_frontend_config(): array {
		return [
			'containerId'   => $this->primary_id(),
			'dataLayerOnly' => (bool) $this->get_option( 'data_layer_only' ),
		];
	}

	public function map_event( string $event_name, array $params ): ?array {
		// GTM consumes events through the dataLayer; the provider name is enough.
		return [
			'event'  => $event_name,
			'params' => $params,
		];
	}
}

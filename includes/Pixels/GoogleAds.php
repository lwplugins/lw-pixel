<?php
/**
 * Google Ads provider (conversion + remarketing).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Loads gtag with the Google Ads conversion id.
 */
final class GoogleAds extends AbstractPixel {

	public function get_id(): string {
		return 'gads';
	}

	public function get_label(): string {
		return __( 'Google Ads', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'gads_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'conversion_id', '' );
	}

	public function get_frontend_config(): array {
		return [
			'conversionId'    => $this->primary_id(),
			'conversionLabel' => (string) $this->get_option( 'conversion_label', '' ),
			'remarketing'     => (bool) $this->get_option( 'remarketing' ),
		];
	}

	public function map_event( string $event_name, array $params ): ?array {
		if ( 'Purchase' !== $event_name ) {
			return null;
		}

		$label = (string) $this->get_option( 'conversion_label', '' );

		if ( '' === $label ) {
			return null;
		}

		return [
			'send_to'        => $this->primary_id() . '/' . $label,
			'value'          => $params['value'] ?? 0,
			'currency'       => $params['currency'] ?? 'USD',
			'transaction_id' => $params['order_id'] ?? '',
		];
	}
}

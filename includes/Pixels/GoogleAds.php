<?php
/**
 * Google Ads provider (conversion tracking).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

use LightweightPlugins\Pixel\Consent\GoogleTagBeforeConsent;

/**
 * Loads gtag with the Google Ads conversion id. The purchase and the events
 * listed under "Other conversions" each send a conversion with their label.
 */
final class GoogleAds extends AbstractPixel implements BaseWithoutConsentInterface {

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

	public function loads_base_without_consent(): bool {
		return GoogleTagBeforeConsent::is_active();
	}

	public function get_frontend_config(): array {
		return [
			'conversionId'       => $this->primary_id(),
			'conversionLabel'    => (string) $this->get_option( 'conversion_label', '' ),
			'baseWithoutConsent' => $this->loads_base_without_consent(),
		];
	}

	public function map_event( string $event_name, array $params ): ?array {
		$label = $this->label_for( $event_name );

		if ( '' === $label ) {
			return null;
		}

		$send_to = $this->primary_id() . '/' . $label;

		if ( 'Purchase' === $event_name ) {
			return [
				'send_to'        => $send_to,
				'value'          => $params['value'] ?? 0,
				'currency'       => $params['currency'] ?? 'USD',
				'transaction_id' => $params['order_id'] ?? '',
			];
		}

		$mapped = [ 'send_to' => $send_to ];

		// A custom event's value (and currency) goes with its conversion.
		if ( isset( $params['value'] ) && is_numeric( $params['value'] ) ) {
			$mapped['value']    = (float) $params['value'];
			$mapped['currency'] = (string) ( $params['currency'] ?? 'USD' );
		}

		return $mapped;
	}

	/**
	 * Conversion label of an event: the purchase label for Purchase, else
	 * the event's line in the conversion list.
	 *
	 * @param string $event_name Generic event name.
	 * @return string Empty when the event sends no conversion.
	 */
	private function label_for( string $event_name ): string {
		$listed = GoogleAdsConversions::parse( (string) $this->get_option( 'conversion_events', '' ) );

		if ( 'Purchase' === $event_name ) {
			$label = (string) $this->get_option( 'conversion_label', '' );

			return '' !== $label ? $label : ( $listed['Purchase'] ?? '' );
		}

		return $listed[ $event_name ] ?? '';
	}
}

<?php
/**
 * Google Analytics 4 (gtag.js) provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Maps generic events to GA4 standard events.
 */
final class GoogleAnalytics4 extends AbstractPixel {

	/**
	 * Generic event → GA4 event name.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'         => 'page_view',
		'ViewContent'      => 'view_item',
		'ViewCategory'     => 'view_item_list',
		'Search'           => 'search',
		'Lead'             => 'generate_lead',
		'Contact'          => 'contact',
		'AddToCart'        => 'add_to_cart',
		'InitiateCheckout' => 'begin_checkout',
		'AddPaymentInfo'   => 'add_payment_info',
		'Purchase'         => 'purchase',
	];

	public function get_id(): string {
		return 'ga4';
	}

	public function get_label(): string {
		return __( 'Google Analytics 4', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'ga4_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'measurement_id', '' );
	}

	public function get_frontend_config(): array {
		return [
			'measurementId' => $this->primary_id(),
			'debug'         => (bool) $this->get_option( 'debug' ),
			'anonymizeIp'   => (bool) $this->get_option( 'anonymize_ip', true ),
		];
	}

	public function map_event( string $event_name, array $params ): array {
		$mapped = self::EVENT_MAP[ $event_name ] ?? $this->snake_case( $event_name );

		return [
			'name'   => $mapped,
			'params' => $params,
		];
	}

	/**
	 * Convert PascalCase to snake_case for unmapped events.
	 *
	 * @param string $name Source name.
	 * @return string
	 */
	private function snake_case( string $name ): string {
		$snake = preg_replace( '/(?<!^)([A-Z])/', '_$1', $name );
		return strtolower( (string) $snake );
	}
}

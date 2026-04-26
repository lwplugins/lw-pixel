<?php
/**
 * X (Twitter) Pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Maps generic events to X (Twitter) twq calls.
 */
final class XPixel extends AbstractPixel {

	/**
	 * Generic → X event names. Note that X uses event IDs, configured per pixel.
	 * We pass the standard event name; the user maps it to their event in X Ads UI.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'         => 'PageView',
		'ViewContent'      => 'ContentView',
		'Search'           => 'Search',
		'Lead'             => 'Lead',
		'Contact'          => 'Contact',
		'AddToCart'        => 'AddToCart',
		'InitiateCheckout' => 'StartCheckout',
		'AddPaymentInfo'   => 'AddPaymentInfo',
		'Purchase'         => 'Purchase',
	];

	public function get_id(): string {
		return 'x';
	}

	public function get_label(): string {
		return __( 'X (Twitter) Pixel', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'x_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'pixel_id', '' );
	}

	public function get_frontend_config(): array {
		return [
			'pixelId' => $this->primary_id(),
		];
	}

	public function map_event( string $event_name, array $params ): ?array {
		$mapped = self::EVENT_MAP[ $event_name ] ?? null;

		if ( null === $mapped ) {
			return null;
		}

		return [
			'name'   => $mapped,
			'params' => $params,
		];
	}
}

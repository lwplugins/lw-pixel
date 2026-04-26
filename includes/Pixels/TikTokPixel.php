<?php
/**
 * TikTok Pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Maps generic events to TikTok ttq calls.
 */
final class TikTokPixel extends AbstractPixel {

	/**
	 * Generic → TikTok event name.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'         => 'Pageview',
		'ViewContent'      => 'ViewContent',
		'Search'           => 'Search',
		'Lead'             => 'SubmitForm',
		'Contact'          => 'Contact',
		'AddToCart'        => 'AddToCart',
		'InitiateCheckout' => 'InitiateCheckout',
		'AddPaymentInfo'   => 'AddPaymentInfo',
		'Purchase'         => 'CompletePayment',
	];

	public function get_id(): string {
		return 'tiktok';
	}

	public function get_label(): string {
		return __( 'TikTok Pixel', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'tiktok_';
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

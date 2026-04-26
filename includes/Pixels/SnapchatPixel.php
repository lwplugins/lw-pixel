<?php
/**
 * Snapchat Pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Maps generic events to Snapchat snaptr calls.
 */
final class SnapchatPixel extends AbstractPixel {

	/**
	 * Generic → Snapchat event.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'         => 'PAGE_VIEW',
		'ViewContent'      => 'VIEW_CONTENT',
		'Search'           => 'SEARCH',
		'Lead'             => 'SIGN_UP',
		'Contact'          => 'SIGN_UP',
		'AddToCart'        => 'ADD_CART',
		'InitiateCheckout' => 'START_CHECKOUT',
		'AddPaymentInfo'   => 'ADD_BILLING',
		'Purchase'         => 'PURCHASE',
	];

	public function get_id(): string {
		return 'snapchat';
	}

	public function get_label(): string {
		return __( 'Snapchat Pixel', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'snapchat_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'pixel_id', '' );
	}

	public function get_frontend_config(): array {
		return [
			'pixelId' => $this->primary_id(),
			'email'   => '',
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

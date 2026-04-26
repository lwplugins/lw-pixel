<?php
/**
 * Reddit Pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Maps generic events to Reddit rdt calls.
 */
final class RedditPixel extends AbstractPixel {

	/**
	 * Generic → Reddit event.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'         => 'PageVisit',
		'ViewContent'      => 'ViewContent',
		'Search'           => 'Search',
		'Lead'             => 'Lead',
		'Contact'          => 'Lead',
		'AddToCart'        => 'AddToCart',
		'InitiateCheckout' => 'AddToCart',
		'AddPaymentInfo'   => 'AddToCart',
		'Purchase'         => 'Purchase',
	];

	public function get_id(): string {
		return 'reddit';
	}

	public function get_label(): string {
		return __( 'Reddit Pixel', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'reddit_';
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

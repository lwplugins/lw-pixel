<?php
/**
 * Microsoft Bing UET tag provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Maps generic events to Bing uetq calls.
 */
final class BingPixel extends AbstractPixel {

	/**
	 * Generic → Bing UET event.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'         => 'page_view',
		'ViewContent'      => 'product_view',
		'Search'           => 'search',
		'Lead'             => 'lead',
		'Contact'          => 'contact',
		'AddToCart'        => 'add_to_cart',
		'InitiateCheckout' => 'begin_checkout',
		'Purchase'         => 'purchase',
	];

	public function get_id(): string {
		return 'bing';
	}

	public function get_label(): string {
		return __( 'Microsoft Bing UET', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'bing_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'tag_id', '' );
	}

	public function get_frontend_config(): array {
		return [
			'tagId' => $this->primary_id(),
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

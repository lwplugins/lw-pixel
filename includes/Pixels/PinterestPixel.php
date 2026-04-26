<?php
/**
 * Pinterest Tag provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Maps generic events to pintrk calls.
 */
final class PinterestPixel extends AbstractPixel {

	/**
	 * Generic → Pinterest event.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'         => 'pagevisit',
		'ViewContent'      => 'pagevisit',
		'ViewCategory'     => 'viewcategory',
		'Search'           => 'search',
		'Lead'             => 'lead',
		'Contact'          => 'lead',
		'AddToCart'        => 'addtocart',
		'InitiateCheckout' => 'checkout',
		'Purchase'         => 'checkout',
	];

	public function get_id(): string {
		return 'pinterest';
	}

	public function get_label(): string {
		return __( 'Pinterest Tag', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'pinterest_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'tag_id', '' );
	}

	public function get_frontend_config(): array {
		return [
			'tagId'     => $this->primary_id(),
			'emEnabled' => (bool) $this->get_option( 'em_enabled' ),
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

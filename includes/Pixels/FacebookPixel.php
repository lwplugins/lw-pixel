<?php
/**
 * Meta (Facebook) Pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Maps generic events to fbq calls.
 */
final class FacebookPixel extends AbstractPixel {

	/**
	 * Event name map (generic → Meta).
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'         => 'PageView',
		'ViewContent'      => 'ViewContent',
		'ViewCategory'     => 'ViewCategory',
		'Search'           => 'Search',
		'Lead'             => 'Lead',
		'Contact'          => 'Contact',
		'AddToCart'        => 'AddToCart',
		'InitiateCheckout' => 'InitiateCheckout',
		'AddPaymentInfo'   => 'AddPaymentInfo',
		'Purchase'         => 'Purchase',
	];

	public function get_id(): string {
		return 'fb';
	}

	public function get_label(): string {
		return __( 'Meta (Facebook) Pixel', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'fb_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'pixel_id', '' );
	}

	public function get_frontend_config(): array {
		return [
			'pixelId'          => $this->primary_id(),
			'advancedMatching' => (bool) $this->get_option( 'advanced_matching' ),
			'capiEnabled'      => (bool) $this->get_option( 'capi_enabled' ),
			'testEventCode'    => (string) $this->get_option( 'test_event_code', '' ),
		];
	}

	public function map_event( string $event_name, array $params ): array {
		$mapped = self::EVENT_MAP[ $event_name ] ?? null;

		if ( null === $mapped ) {
			$mapped = $event_name;
			$type   = 'trackCustom';
		} else {
			$type = 'track';
		}

		return [
			'type'   => $type,
			'name'   => $mapped,
			'params' => $this->normalize_params( $params ),
		];
	}

	/**
	 * Drop empty params, ensure scalar values.
	 *
	 * @param array<string, mixed> $params Event params.
	 * @return array<string, mixed>
	 */
	private function normalize_params( array $params ): array {
		return array_filter(
			$params,
			static fn ( mixed $value ): bool => null !== $value && '' !== $value
		);
	}
}

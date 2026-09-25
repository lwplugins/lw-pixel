<?php
/**
 * Maps LW Pixel's generic events to ChatGPT Ads events.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\ChatGpt;

/**
 * Shared by the browser pixel and the Conversions API, so both send the
 * same event type and data (required for deduplication).
 */
final class EventMapper {

	/**
	 * Generic event → ChatGPT Ads event type.
	 *
	 * @var array<string, string>
	 */
	private const TYPES = [
		'PageView'             => 'page_viewed',
		'ViewContent'          => 'contents_viewed',
		'AddToCart'            => 'items_added',
		'InitiateCheckout'     => 'checkout_started',
		'Purchase'             => 'order_created',
		'Lead'                 => 'lead_created',
		'CompleteRegistration' => 'registration_completed',
	];

	/**
	 * ChatGPT Ads event type → data shape.
	 *
	 * @var array<string, string>
	 */
	private const SHAPES = [
		'page_viewed'            => 'contents',
		'contents_viewed'        => 'contents',
		'items_added'            => 'contents',
		'checkout_started'       => 'contents',
		'order_created'          => 'contents',
		'lead_created'           => 'customer_action',
		'registration_completed' => 'customer_action',
	];

	/**
	 * Built-in LW Pixel events with no ChatGPT Ads equivalent. They are
	 * skipped rather than sent as custom events.
	 */
	private const UNMAPPED = [
		'ViewCategory',
		'ViewCart',
		'Search',
		'AddPaymentInfo',
		'Contact',
		'Login',
		'Comment',
		'Scroll',
		'TimeOnPage',
		'Download',
	];

	/**
	 * Map a generic event.
	 *
	 * @param string               $name   Generic event name.
	 * @param array<string, mixed> $params Generic params.
	 * @return array{type: string, data: array<string, mixed>, custom_event_name?: string}|null
	 */
	public static function map( string $name, array $params ): ?array {
		if ( in_array( $name, self::UNMAPPED, true ) ) {
			return null;
		}

		$type = self::TYPES[ $name ] ?? null;

		if ( null === $type ) {
			$custom = CustomEventName::normalize( $name );

			if ( '' === $custom ) {
				return null;
			}

			return [
				'type'              => 'custom',
				'data'              => array_merge( [ 'type' => 'custom' ], self::amount( $params ) ),
				'custom_event_name' => $custom,
			];
		}

		$shape = self::SHAPES[ $type ];
		$data  = array_merge( [ 'type' => $shape ], self::amount( $params ) );

		if ( 'contents' === $shape && 'page_viewed' !== $type ) {
			$contents = self::contents( $params );

			if ( [] !== $contents ) {
				$data['contents'] = $contents;
			}
		}

		return [
			'type' => $type,
			'data' => $data,
		];
	}

	/**
	 * Event-level amount + currency, when a positive value is known.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>
	 */
	private static function amount( array $params ): array {
		$currency = MinorUnits::code( $params['currency'] ?? '' );
		$value    = self::value( $params );

		if ( '' === $currency || $value <= 0 ) {
			return [];
		}

		return [
			'amount'   => MinorUnits::to_minor( $value, $currency ),
			'currency' => $currency,
		];
	}

	/**
	 * Decimal event value: `value`, or price × quantity for a single product.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return float
	 */
	private static function value( array $params ): float {
		if ( isset( $params['value'] ) && is_numeric( $params['value'] ) ) {
			return (float) $params['value'];
		}

		if ( isset( $params['price'] ) && is_numeric( $params['price'] ) ) {
			return (float) $params['price'] * max( 1, (int) ( $params['quantity'] ?? 1 ) );
		}

		return 0.0;
	}

	/**
	 * Build the contents list from a cart/order (`contents`) or a single item.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<int, array<string, mixed>>
	 */
	private static function contents( array $params ): array {
		$items = isset( $params['contents'] ) && is_array( $params['contents'] )
			? $params['contents']
			: ( isset( $params['content_id'] ) ? [ $params ] : [] );

		$out = [];

		foreach ( $items as $item ) {
			if ( is_array( $item ) ) {
				$out[] = self::item( $item );
			}
		}

		return array_values( array_filter( $out ) );
	}

	/**
	 * One content item. `amount` is the unit price in minor units.
	 *
	 * @param array<string, mixed> $item Generic item.
	 * @return array<string, mixed>
	 */
	private static function item( array $item ): array {
		$currency = MinorUnits::code( $item['currency'] ?? '' );
		$type     = (string) ( $item['content_type'] ?? '' );
		$out      = [
			'id'           => (string) ( $item['content_id'] ?? '' ),
			'name'         => (string) ( $item['content_name'] ?? '' ),
			'content_type' => 'product' === $type ? 'product' : 'page',
		];

		if ( isset( $item['quantity'] ) ) {
			$out['quantity'] = max( 1, (int) $item['quantity'] );
		}

		if ( '' !== $currency && isset( $item['price'] ) && is_numeric( $item['price'] ) ) {
			$out['amount']   = MinorUnits::to_minor( (float) $item['price'], $currency );
			$out['currency'] = $currency;
		}

		return array_filter( $out, static fn ( $value ): bool => '' !== $value );
	}
}

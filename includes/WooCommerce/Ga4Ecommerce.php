<?php
/**
 * GA4 ecommerce dataLayer shape for Google Tag Manager.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce;

use LightweightPlugins\Pixel\Options;
use WC_Order;

/**
 * Turns the generic WooCommerce events into GA4 ecommerce events
 * (`event` + `ecommerce` object), the shape GTM's GA4 and Google Ads tags
 * read with "Send ecommerce data". The purchase reuses Ga4Purchase, so it
 * carries the same value and items as the gtag and Measurement Protocol
 * copies; only its transaction_id is the order number shown to the customer.
 * The gross prices option puts the tax-inclusive unit price on the purchase
 * items (Merchant Center feeds list gross prices); the value stays net.
 */
final class Ga4Ecommerce {

	/**
	 * Generic event → GA4 ecommerce event.
	 *
	 * @var array<string, string>
	 */
	private const EVENTS = [
		'ViewContent'      => 'view_item',
		'AddToCart'        => 'add_to_cart',
		'InitiateCheckout' => 'begin_checkout',
		'Purchase'         => 'purchase',
	];

	/**
	 * The GA4 event name and ecommerce object, or null for any other event.
	 *
	 * @param string               $event_name Generic event name.
	 * @param array<string, mixed> $params     Generic params (ProductData).
	 * @return array{event: string, ecommerce: array<string, mixed>}|null
	 */
	public static function for_event( string $event_name, array $params ): ?array {
		$name = self::EVENTS[ $event_name ] ?? null;
		if ( null === $name ) {
			return null;
		}

		if ( 'Purchase' === $event_name ) {
			return isset( $params['order_id'] ) ? [
				'event'     => $name,
				'ecommerce' => self::purchase( $params ),
			] : null;
		}

		$items = self::items( $params );
		if ( [] === $items ) {
			return null;
		}

		$value = 0.0;
		foreach ( $items as $item ) {
			$value += $item['price'] * $item['quantity'];
		}

		return [
			'event'     => $name,
			'ecommerce' => [
				'currency' => (string) ( $params['currency'] ?? '' ),
				'value'    => round( $value, 2 ),
				'items'    => $items,
			],
		];
	}

	/**
	 * The purchase, with the order number as transaction_id.
	 *
	 * @param array<string, mixed> $params Generic Purchase params.
	 * @return array<string, mixed>
	 */
	private static function purchase( array $params ): array {
		$ecommerce = Ga4Purchase::params( $params, (bool) Options::get( 'gtm_ga4_gross_prices' ) );
		$order     = function_exists( 'wc_get_order' ) ? wc_get_order( (int) $params['order_id'] ) : false;

		if ( $order instanceof WC_Order ) {
			$ecommerce['transaction_id'] = (string) $order->get_order_number();
		}

		return $ecommerce;
	}

	/**
	 * GA4 items from a single product's params or a cart's contents.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<int, array<string, mixed>>
	 */
	private static function items( array $params ): array {
		$contents = isset( $params['contents'] ) ? (array) $params['contents'] : [];

		// A product page's ViewContent and AddToCart carry one product
		// inline; a post's ViewContent has no price and is not ecommerce.
		if ( [] === $contents && 'product' === ( $params['content_type'] ?? '' ) ) {
			$contents = [ $params ];
		}

		$items = [];
		foreach ( $contents as $content ) {
			if ( is_array( $content ) && isset( $content['content_id'] ) ) {
				$items[] = Ga4Purchase::item(
					(string) $content['content_id'],
					(string) ( $content['content_name'] ?? '' ),
					(float) ( $content['price'] ?? 0 ),
					max( 1, (int) ( $content['quantity'] ?? 1 ) )
				);
			}
		}

		return $items;
	}
}

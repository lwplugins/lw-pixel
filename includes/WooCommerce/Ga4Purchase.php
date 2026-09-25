<?php
/**
 * GA4 ecommerce shape of an order's purchase.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce;

use LightweightPlugins\Pixel\Options;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

/**
 * The browser (gtag) purchase and the Measurement Protocol purchase share
 * this one builder, so both carry the same transaction_id, value and items.
 *
 * GA4's purchase `value` is "the sum of (price * quantity) for all items.
 * Don't include shipping or tax", and an item's `price` is the discounted
 * unit price. So the figures come from the order's line totals excluding
 * tax, not from the generic (tax-inclusive, shipping-inclusive) value.
 */
final class Ga4Purchase {

	/**
	 * GA4 purchase params (transaction_id, value, currency, items).
	 *
	 * @param array<string, mixed> $params Generic Purchase params (ProductData::for_order()).
	 * @return array<string, mixed>
	 */
	public static function params( array $params ): array {
		$order_id = (string) ( $params['order_id'] ?? '' );
		$order    = '' !== $order_id && function_exists( 'wc_get_order' ) ? wc_get_order( (int) $order_id ) : false;

		$out = $order instanceof WC_Order ? self::from_order( $order ) : self::from_params( $params );

		if ( '' !== $order_id ) {
			$out['transaction_id'] = $order_id;
		}

		$currency = (string) ( $params['currency'] ?? '' );
		if ( '' !== $currency ) {
			$out['currency'] = $currency;
		}

		return $out;
	}

	/**
	 * Items and value from the order's line totals (after discounts, before tax).
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, mixed>
	 */
	private static function from_order( WC_Order $order ): array {
		$items = [];
		$value = 0.0;

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$quantity = max( 1, (int) $item->get_quantity() );
			$price    = round( (float) $order->get_item_total( $item, false, false ), 2 );
			$value   += $price * $quantity;
			$items[]  = self::item( ProductData::content_id( $product ), $product->get_name(), $price, $quantity );
		}

		return [
			'value' => round( $value, 2 ),
			'items' => $items,
		];
	}

	/**
	 * Fallback when the order cannot be loaded: the generic contents.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>
	 */
	private static function from_params( array $params ): array {
		$items = [];
		$value = 0.0;

		foreach ( (array) ( $params['contents'] ?? [] ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$quantity = max( 1, (int) ( $item['quantity'] ?? 1 ) );
			$price    = (float) ( $item['price'] ?? 0 );
			$value   += $price * $quantity;
			$items[]  = self::item( (string) ( $item['content_id'] ?? '' ), (string) ( $item['content_name'] ?? '' ), $price, $quantity );
		}

		return [
			'value' => round( $value, 2 ),
			'items' => $items,
		];
	}

	/**
	 * One GA4 item. Medical mode leaves the product name out.
	 *
	 * @param string $id       Item id.
	 * @param string $name     Product name.
	 * @param float  $price    Discounted unit price, excluding tax.
	 * @param int    $quantity Quantity.
	 * @return array<string, mixed>
	 */
	private static function item( string $id, string $name, float $price, int $quantity ): array {
		$item = [
			'item_id'   => $id,
			'item_name' => $name,
			'price'     => $price,
			'quantity'  => $quantity,
		];

		if ( Options::get( 'compliance_medical' ) ) {
			unset( $item['item_name'] );
		}

		return $item;
	}
}

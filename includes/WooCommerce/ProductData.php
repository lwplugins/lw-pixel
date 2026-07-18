<?php
/**
 * WooCommerce product data extractor.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\WooCommerce;

use LightweightPlugins\Pixel\Options;
use WC_Product;

/**
 * Builds standardised product params for events.
 */
final class ProductData {

	/**
	 * Build a content array for a single product.
	 *
	 * @param WC_Product $product  Product instance.
	 * @param int        $quantity Quantity (default 1).
	 * @return array<string, mixed>
	 */
	public static function for_product( WC_Product $product, int $quantity = 1 ): array {
		return [
			'content_id'   => self::content_id( $product ),
			'content_name' => $product->get_name(),
			'content_type' => 'product',
			'quantity'     => $quantity,
			'price'        => self::price( $product ),
			'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
		];
	}

	/**
	 * Build params for the cart contents.
	 *
	 * @return array<string, mixed>
	 */
	public static function for_cart(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return [];
		}

		$contents = [];
		$total    = 0.0;

		foreach ( WC()->cart->get_cart() as $item ) {
			$product = $item['data'] ?? null;

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$content    = self::for_product( $product, (int) ( $item['quantity'] ?? 1 ) );
			$contents[] = $content;
			$total     += (float) $content['price'] * $content['quantity'];
		}

		return [
			'contents'  => $contents,
			'value'     => $total,
			'num_items' => count( $contents ),
			'currency'  => $contents[0]['currency'] ?? 'USD',
		];
	}

	/**
	 * Build params for an order (used after checkout).
	 *
	 * @param int $order_id Order id.
	 * @return array<string, mixed>
	 */
	public static function for_order( int $order_id ): array {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;

		if ( ! $order ) {
			return [];
		}

		$contents = [];

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$contents[] = self::for_product( $product, (int) $item->get_quantity() );
		}

		$value = (bool) Options::get( 'woo_send_value_with_tax', true )
			? (float) $order->get_total()
			: (float) $order->get_subtotal();

		return [
			'contents'  => $contents,
			'value'     => $value,
			'currency'  => $order->get_currency(),
			'order_id'  => (string) $order->get_id(),
			'num_items' => count( $contents ),
		];
	}

	/**
	 * Resolve the content_id used by ad networks (SKU or product id).
	 *
	 * @param WC_Product $product Product.
	 * @return string
	 */
	private static function content_id( WC_Product $product ): string {
		$prefix  = (string) Options::get( 'woo_content_id_prefix', '' );
		$use_sku = (bool) Options::get( 'woo_use_sku', false );
		$id      = $use_sku && $product->get_sku() ? $product->get_sku() : (string) $product->get_id();

		return $prefix . $id;
	}

	/**
	 * Resolve the price for a product.
	 *
	 * @param WC_Product $product Product.
	 * @return float
	 */
	private static function price( WC_Product $product ): float {
		$with_tax = (bool) Options::get( 'woo_send_value_with_tax', true );

		if ( $with_tax && function_exists( 'wc_get_price_including_tax' ) ) {
			return (float) wc_get_price_including_tax( $product );
		}

		return (float) $product->get_price();
	}
}

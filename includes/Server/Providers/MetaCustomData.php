<?php
/**
 * Meta Conversions API custom_data builder.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server\Providers;

/**
 * Turns LW Pixel's generic event params into Meta's custom_data block.
 */
final class MetaCustomData {

	/**
	 * Build custom_data.
	 *
	 * @param string               $name   Generic event name.
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>
	 */
	public static function build( string $name, array $params ): array {
		$data = [];

		if ( isset( $params['contents'] ) && is_array( $params['contents'] ) ) {
			$data = self::from_items( $params['contents'] );
		} elseif ( isset( $params['content_id'] ) ) {
			$data = self::from_items( [ $params ] );

			if ( '' !== (string) ( $params['content_name'] ?? '' ) ) {
				$data['content_name'] = (string) $params['content_name'];
			}
		}

		// Meta requires value and currency on a Purchase, so a free order
		// (100% coupon, free product) sends 0 instead of leaving them out.
		$value = self::value( $params );
		if ( ( $value > 0 || 'Purchase' === $name ) && '' !== (string) ( $params['currency'] ?? '' ) ) {
			$data['value']    = $value;
			$data['currency'] = (string) $params['currency'];
		}

		if ( isset( $params['num_items'] ) ) {
			$data['num_items'] = (int) $params['num_items'];
		}

		if ( '' !== (string) ( $params['order_id'] ?? '' ) ) {
			$data['order_id'] = (string) $params['order_id'];
		}

		if ( 'Search' === $name && '' !== (string) ( $params['search_string'] ?? '' ) ) {
			$data['search_string'] = (string) $params['search_string'];
		}

		if ( 'Lead' === $name && '' !== (string) ( $params['form_name'] ?? '' ) ) {
			$data['content_name'] = (string) $params['form_name'];
		}

		return $data;
	}

	/**
	 * Content ids + contents list from product items.
	 *
	 * @param array<int|string, mixed> $items Generic items.
	 * @return array<string, mixed>
	 */
	private static function from_items( array $items ): array {
		$contents = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || '' === (string) ( $item['content_id'] ?? '' ) ) {
				continue;
			}

			$contents[] = [
				'id'         => (string) $item['content_id'],
				'quantity'   => max( 1, (int) ( $item['quantity'] ?? 1 ) ),
				'item_price' => (float) ( $item['price'] ?? 0 ),
			];
		}

		if ( [] === $contents ) {
			return [];
		}

		return [
			'content_ids'  => array_column( $contents, 'id' ),
			'contents'     => $contents,
			'content_type' => 'product',
		];
	}

	/**
	 * Event value: `value`, or price × quantity for a single product.
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
}

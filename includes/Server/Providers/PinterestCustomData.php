<?php
/**
 * Pinterest Conversions API custom_data builder.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server\Providers;

/**
 * Turns LW Pixel's generic event params into Pinterest's custom_data block.
 * Pinterest takes `value` and `item_price` as strings.
 */
final class PinterestCustomData {

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
		}

		if ( '' !== (string) ( $params['content_name'] ?? '' ) && ! isset( $params['contents'] ) ) {
			$data['content_name'] = (string) $params['content_name'];
		}

		if ( 'Lead' === $name && '' !== (string) ( $params['form_name'] ?? '' ) ) {
			$data['content_name'] = (string) $params['form_name'];
		}

		if ( '' !== (string) ( $params['content_category'] ?? '' ) ) {
			$data['content_category'] = (string) $params['content_category'];
		}

		$value = self::value( $params );
		if ( null !== $value && ( $value > 0 || 'Purchase' === $name ) ) {
			$data['value'] = self::money( $value );
		}

		if ( isset( $data['value'] ) && '' !== (string) ( $params['currency'] ?? '' ) ) {
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

			$content = [
				'id'         => (string) $item['content_id'],
				'item_price' => self::money( (float) ( $item['price'] ?? 0 ) ),
				'quantity'   => max( 1, (int) ( $item['quantity'] ?? 1 ) ),
			];

			if ( '' !== (string) ( $item['content_name'] ?? '' ) ) {
				$content['item_name'] = (string) $item['content_name'];
			}

			$contents[] = $content;
		}

		if ( [] === $contents ) {
			return [];
		}

		return [
			'content_ids' => array_column( $contents, 'id' ),
			'contents'    => $contents,
		];
	}

	/**
	 * Event value: `value`, or price × quantity for a single product.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return float|null
	 */
	private static function value( array $params ): ?float {
		if ( isset( $params['value'] ) && is_numeric( $params['value'] ) ) {
			return (float) $params['value'];
		}

		if ( isset( $params['price'] ) && is_numeric( $params['price'] ) ) {
			return (float) $params['price'] * max( 1, (int) ( $params['quantity'] ?? 1 ) );
		}

		return null;
	}

	/**
	 * A money amount as a plain decimal string ("12.5", "0").
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	private static function money( float $amount ): string {
		$formatted = rtrim( rtrim( number_format( $amount, 4, '.', '' ), '0' ), '.' );

		return '' === $formatted || '-0' === $formatted ? '0' : $formatted;
	}
}

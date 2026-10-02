<?php
/**
 * Maps LW Pixel's generic events to Barion Pixel events.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Barion;

/**
 * Builds the `bp('track', name, data)` payloads. The shapes follow the
 * validation in bp.js: a missing mandatory key makes bp.js drop the event,
 * an unknown key is reported as an error, so an event that cannot be built
 * completely is skipped here instead of being sent half-filled.
 */
final class EventMapper {

	/**
	 * Unit sent with every product (bp.js requires one).
	 */
	private const UNIT = 'pcs';

	/**
	 * Generic event → Barion event. PageView is sent by bp.js itself.
	 *
	 * @var array<string, string>
	 */
	private const EVENTS = [
		'ViewContent'      => 'contentView',
		'AddToCart'        => 'addToCart',
		'InitiateCheckout' => 'initiateCheckout',
		'Purchase'         => 'purchase',
		'ViewCategory'     => 'categorySelection',
		'Search'           => 'search',
	];

	/**
	 * Map a generic event.
	 *
	 * @param string               $name   Generic event name.
	 * @param array<string, mixed> $params Generic params.
	 * @return array{name: string, data: array<string, mixed>}|null
	 */
	public static function map( string $name, array $params ): ?array {
		$data = match ( $name ) {
			'ViewContent'      => self::content_view( $params ),
			'AddToCart'        => self::add_to_cart( $params ),
			'InitiateCheckout' => self::cart( $params ),
			'Purchase'         => self::purchase( $params ),
			'ViewCategory'     => self::category( $params ),
			'Search'           => self::search( $params ),
			default            => null,
		};

		if ( null === $data ) {
			return null;
		}

		return [
			'name' => self::EVENTS[ $name ],
			'data' => $data,
		];
	}

	/**
	 * `contentView`: a product with its price, or a page/article.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>|null
	 */
	private static function content_view( array $params ): ?array {
		if ( 'product' === ( $params['content_type'] ?? '' ) ) {
			$item = self::item( $params, 1 );

			if ( null !== $item ) {
				// bp.js rejects totalItemPrice on contentView.
				unset( $item['totalItemPrice'] );
			}

			return $item;
		}

		$id = (string) ( $params['content_id'] ?? '' );

		if ( '' === $id ) {
			return null;
		}

		return [
			'contentType' => 'post' === ( $params['content_type'] ?? '' ) ? 'Article' : 'Page',
			'id'          => $id,
			'name'        => self::name( $params ),
		];
	}

	/**
	 * `addToCart`: the added product, with the added quantity.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>|null
	 */
	private static function add_to_cart( array $params ): ?array {
		$item = self::item( $params, max( 1, (int) ( $params['quantity'] ?? 1 ) ) );

		if ( null === $item ) {
			return null;
		}

		return $item + [ 'step' => 1 ];
	}

	/**
	 * `initiateCheckout`: the cart lines and the cart value.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>|null
	 */
	private static function cart( array $params ): ?array {
		$contents = self::contents( $params );
		$currency = self::currency( $params );

		if ( [] === $contents || '' === $currency || ! is_numeric( $params['value'] ?? null ) ) {
			return null;
		}

		return [
			'contents' => $contents,
			'currency' => $currency,
			'revenue'  => (float) $params['value'],
			'step'     => 1,
		];
	}

	/**
	 * `purchase`: the cart shape plus the order number.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>|null
	 */
	private static function purchase( array $params ): ?array {
		$data = self::cart( $params );

		if ( null !== $data && '' !== (string) ( $params['order_id'] ?? '' ) ) {
			$data['orderNumber'] = (string) $params['order_id'];
		}

		return $data;
	}

	/**
	 * `categorySelection`: the product category.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>|null
	 */
	private static function category( array $params ): ?array {
		$id   = (string) ( $params['category_id'] ?? '' );
		$name = (string) ( $params['content_category'] ?? '' );

		if ( '' === $id ) {
			return null;
		}

		return [
			'id'   => $id,
			'name' => '' !== $name ? $name : $id,
		];
	}

	/**
	 * `search`: the search string (dropped by medical mode, then skipped).
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<string, mixed>|null
	 */
	private static function search( array $params ): ?array {
		$search = (string) ( $params['search_string'] ?? '' );

		return '' === $search ? null : [ 'searchString' => $search ];
	}

	/**
	 * Cart or order lines; empty when any line is incomplete.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return array<int, array<string, mixed>>
	 */
	private static function contents( array $params ): array {
		$out = [];

		foreach ( (array) ( $params['contents'] ?? [] ) as $line ) {
			$item = is_array( $line ) ? self::item( $line, max( 1, (int) ( $line['quantity'] ?? 1 ) ) ) : null;

			if ( null === $item ) {
				return [];
			}

			$out[] = $item;
		}

		return $out;
	}

	/**
	 * One product with every key bp.js requires of a content item.
	 *
	 * @param array<string, mixed> $params   Generic product params.
	 * @param int                  $quantity Quantity.
	 * @return array<string, mixed>|null
	 */
	private static function item( array $params, int $quantity ): ?array {
		$id       = (string) ( $params['content_id'] ?? '' );
		$currency = self::currency( $params );

		if ( '' === $id || '' === $currency || ! is_numeric( $params['price'] ?? null ) ) {
			return null;
		}

		$price = (float) $params['price'];

		return [
			'contentType'    => 'Product',
			'currency'       => $currency,
			'id'             => $id,
			'name'           => self::name( $params ),
			'quantity'       => $quantity,
			'unit'           => self::UNIT,
			'unitPrice'      => $price,
			'totalItemPrice' => round( $price * $quantity, 4 ),
		];
	}

	/**
	 * Display name; the id when the name was removed (medical mode).
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return string
	 */
	private static function name( array $params ): string {
		$name = (string) ( $params['content_name'] ?? '' );

		return '' !== $name ? $name : (string) ( $params['content_id'] ?? '' );
	}

	/**
	 * Upper-case ISO 4217 code, or '' when missing.
	 *
	 * @param array<string, mixed> $params Generic params.
	 * @return string
	 */
	private static function currency( array $params ): string {
		$code = strtoupper( trim( (string) ( $params['currency'] ?? '' ) ) );

		return 1 === preg_match( '/^[A-Z]{3}$/', $code ) ? $code : '';
	}
}

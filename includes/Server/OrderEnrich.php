<?php
/**
 * WooCommerce order enrichment — fires CAPI Purchase events when an order's
 * status changes (e.g. pending → completed) so the pixel team gets the final
 * payment value rather than the intermediate state captured at thank-you time.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\AdvancedMatching\UserDataBuilder;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\WooCommerce\ProductData;

/**
 * Listens for WC order completion / processing and dispatches a Purchase to CAPI.
 */
final class OrderEnrich {

	private const TRACKED_META = '_lw_pixel_capi_purchase_tracked';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! Options::get( 'fb_capi_enabled' ) || ! Options::get( 'fb_order_enrich' ) ) {
			return;
		}

		add_action( 'woocommerce_order_status_completed', [ self::class, 'enrich' ] );
		add_action( 'woocommerce_order_status_processing', [ self::class, 'enrich' ] );
	}

	/**
	 * Send a Purchase to CAPI for the given order, if not yet tracked.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public static function enrich( int $order_id ): void {
		if ( $order_id <= 0 || self::already_tracked( $order_id ) ) {
			return;
		}

		$params = ProductData::for_order( $order_id );
		if ( [] === $params ) {
			return;
		}

		FacebookCAPI::send_event(
			'Purchase',
			self::custom_data( $params ),
			UserDataBuilder::for_order( $order_id )
		);

		update_post_meta( $order_id, self::TRACKED_META, '1' );
	}

	/**
	 * Build the custom_data block for the Meta CAPI Purchase event.
	 *
	 * @param array<string, mixed> $params Order params.
	 * @return array<string, mixed>
	 */
	private static function custom_data( array $params ): array {
		$contents = [];

		foreach ( (array) ( $params['contents'] ?? [] ) as $item ) {
			$contents[] = [
				'id'         => $item['content_id'] ?? '',
				'quantity'   => (int) ( $item['quantity'] ?? 1 ),
				'item_price' => (float) ( $item['price'] ?? 0 ),
			];
		}

		return [
			'currency'     => $params['currency'] ?? 'USD',
			'value'        => (float) ( $params['value'] ?? 0 ),
			'order_id'     => (string) ( $params['order_id'] ?? '' ),
			'num_items'    => (int) ( $params['num_items'] ?? count( $contents ) ),
			'contents'     => $contents,
			'content_type' => 'product',
		];
	}

	/**
	 * Whether this order's CAPI Purchase has been sent.
	 *
	 * @param int $order_id Order id.
	 * @return bool
	 */
	private static function already_tracked( int $order_id ): bool {
		return (bool) get_post_meta( $order_id, self::TRACKED_META, true );
	}
}

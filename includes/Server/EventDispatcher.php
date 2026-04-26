<?php
/**
 * Server-side event dispatcher.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Options;

/**
 * Decides which providers should receive a server-side copy of an event.
 */
final class EventDispatcher {

	/**
	 * Dispatch an event to all configured server-side endpoints.
	 *
	 * @param string               $event_name  Event name.
	 * @param array<string, mixed> $custom_data Custom data block.
	 * @param array<string, mixed> $user_data   User data block.
	 * @return array<string, array{ok: bool, body: string}>
	 */
	public static function dispatch( string $event_name, array $custom_data = [], array $user_data = [] ): array {
		$results = [];

		if ( Options::get( 'fb_capi_enabled' ) ) {
			$results['fb'] = FacebookCAPI::send_event( $event_name, $custom_data, $user_data );
		}

		if ( Options::get( 'ga4_mp_enabled' ) ) {
			$results['ga4'] = GoogleAnalyticsMP::send_event(
				self::generic_to_ga4( $event_name ),
				$custom_data
			);
		}

		/**
		 * Allow third-party providers to dispatch their own server-side copy.
		 *
		 * @param array $results Provider id → result.
		 * @param string $event_name Event name.
		 * @param array $custom_data Custom data block.
		 * @param array $user_data User data block.
		 */
		return (array) apply_filters( 'lw_pixel_server_dispatch_results', $results, $event_name, $custom_data, $user_data );
	}

	/**
	 * Map a generic event name to the GA4 event name.
	 *
	 * @param string $name Generic event name.
	 * @return string
	 */
	private static function generic_to_ga4( string $name ): string {
		$map = [
			'PageView'         => 'page_view',
			'ViewContent'      => 'view_item',
			'ViewCategory'     => 'view_item_list',
			'Search'           => 'search',
			'Lead'             => 'generate_lead',
			'AddToCart'        => 'add_to_cart',
			'InitiateCheckout' => 'begin_checkout',
			'Purchase'         => 'purchase',
		];

		return $map[ $name ] ?? strtolower( preg_replace( '/(?<!^)([A-Z])/', '_$1', $name ) ?? $name );
	}
}

<?php
/**
 * Build hashed user-data for Meta CAPI from the current user / WC order.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\AdvancedMatching;

use LightweightPlugins\Pixel\Options;

/**
 * Collects user-data from logged-in user / WC checkout / order and hashes it.
 */
final class UserDataBuilder {

	/**
	 * Build hashed user_data for the current request.
	 *
	 * @return array<string, string>
	 */
	public static function for_current_request(): array {
		if ( ! Options::get( 'fb_advanced_matching' ) ) {
			return [];
		}

		$raw = self::collect_from_user();

		if ( Options::get( 'fb_send_external_id' ) ) {
			$user_id = get_current_user_id();
			if ( $user_id > 0 ) {
				$raw['external_id'] = (string) $user_id;
			}
		}

		return Hasher::build_user_data( $raw );
	}

	/**
	 * Build hashed user_data from a WooCommerce order.
	 *
	 * @param int $order_id Order id.
	 * @return array<string, string>
	 */
	public static function for_order( int $order_id ): array {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return [];
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return [];
		}

		$raw = [
			'em'      => (string) $order->get_billing_email(),
			'ph'      => (string) $order->get_billing_phone(),
			'fn'      => (string) $order->get_billing_first_name(),
			'ln'      => (string) $order->get_billing_last_name(),
			'ct'      => (string) $order->get_billing_city(),
			'st'      => (string) $order->get_billing_state(),
			'zp'      => (string) $order->get_billing_postcode(),
			'country' => (string) $order->get_billing_country(),
		];

		if ( Options::get( 'fb_send_external_id' ) ) {
			$customer_id = (int) $order->get_customer_id();
			if ( $customer_id > 0 ) {
				$raw['external_id'] = (string) $customer_id;
			}
		}

		return Hasher::build_user_data( $raw );
	}

	/**
	 * Collect raw user-data from a logged-in user.
	 *
	 * @return array<string, string>
	 */
	private static function collect_from_user(): array {
		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			return [];
		}

		return [
			'em' => (string) $user->user_email,
			'fn' => (string) $user->first_name,
			'ln' => (string) $user->last_name,
		];
	}
}

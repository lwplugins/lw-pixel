<?php
/**
 * Raw customer data for hashed matching.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\AdvancedMatching\PhoneNormalizer;

/**
 * Collects unhashed customer fields in one neutral shape; each provider
 * normalises and hashes them per its own spec, and only when its advanced
 * matching option is on.
 *
 * Keys: email, phone, calling_code, first_name, last_name, city, region,
 * postal_code, country, external_id.
 */
final class CustomerData {

	/**
	 * The logged-in user making the current request ([] for guests).
	 *
	 * @return array<string, string>
	 */
	public static function current_user(): array {
		$user = wp_get_current_user();

		if ( ! $user->exists() ) {
			return [];
		}

		$meta    = static fn ( string $key ): string => (string) get_user_meta( $user->ID, $key, true );
		$country = $meta( 'billing_country' );

		return array_filter(
			[
				'email'        => (string) $user->user_email,
				'first_name'   => (string) $user->first_name,
				'last_name'    => (string) $user->last_name,
				'external_id'  => (string) $user->ID,
				'phone'        => $meta( 'billing_phone' ),
				'calling_code' => PhoneNormalizer::calling_code( $country ),
				'city'         => $meta( 'billing_city' ),
				'region'       => $meta( 'billing_state' ),
				'postal_code'  => $meta( 'billing_postcode' ),
				'country'      => $country,
			]
		);
	}

	/**
	 * The billing customer of an order. Nothing is read from the current
	 * request: order events can run in an admin, cron or webhook request.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<string, string>
	 */
	public static function from_order( \WC_Order $order ): array {
		$country     = (string) $order->get_billing_country();
		$customer_id = (int) $order->get_customer_id();

		return array_filter(
			[
				'email'        => (string) $order->get_billing_email(),
				'first_name'   => (string) $order->get_billing_first_name(),
				'last_name'    => (string) $order->get_billing_last_name(),
				'external_id'  => $customer_id > 0 ? (string) $customer_id : '',
				'phone'        => (string) $order->get_billing_phone(),
				'calling_code' => PhoneNormalizer::calling_code( $country ),
				'city'         => (string) $order->get_billing_city(),
				'region'       => (string) $order->get_billing_state(),
				'postal_code'  => (string) $order->get_billing_postcode(),
				'country'      => $country,
			]
		);
	}
}

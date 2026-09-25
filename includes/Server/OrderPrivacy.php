<?php
/**
 * WooCommerce privacy export / erasure of the checkout context.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

/**
 * The checkout context (IP, user agent, `_fbp`/`_fbc`, GA client id,
 * `__obref`/`__oppref`) is personal data kept on the order until every
 * provider has sent the Purchase. WooCommerce's personal data exporter
 * lists it, and its eraser deletes it (instead of leaving a `[deleted]`
 * placeholder).
 *
 * Registered even with every server-side provider off: older orders may
 * still carry the context.
 */
final class OrderPrivacy {

	/**
	 * Register the WooCommerce privacy filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'woocommerce_privacy_export_order_personal_data_meta', [ self::class, 'export_keys' ] );
		add_filter( 'woocommerce_privacy_export_order_personal_data_meta_value', [ self::class, 'export_value' ], 10, 2 );
		add_filter( 'woocommerce_privacy_remove_order_personal_data_meta', [ self::class, 'erase_keys' ] );
		add_filter( 'woocommerce_privacy_remove_order_personal_data_meta_value', [ self::class, 'erase_value' ], 10, 2 );
	}

	/**
	 * Exported order meta: key → label.
	 *
	 * @param mixed $keys Meta keys.
	 * @return array<string, string>
	 */
	public static function export_keys( $keys ): array {
		$keys = is_array( $keys ) ? $keys : [];

		$keys[ CheckoutContext::META_KEY ] = __( 'Tracking context (LW Pixel)', 'lw-pixel' );

		return $keys;
	}

	/**
	 * The stored context as readable "key: value" text.
	 *
	 * @param mixed  $value    Meta value.
	 * @param string $meta_key Meta key.
	 * @return mixed
	 */
	public static function export_value( $value, $meta_key ) {
		if ( CheckoutContext::META_KEY !== $meta_key || ! is_array( $value ) ) {
			return $value;
		}

		$lines = [];

		foreach ( $value as $key => $item ) {
			$item = is_array( $item ) ? implode( ', ', array_map( 'strval', $item ) ) : (string) $item;

			if ( '' !== $item ) {
				$lines[] = $key . ': ' . $item;
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Order meta the eraser handles: key → data type.
	 *
	 * @param mixed $keys Meta keys.
	 * @return array<string, string>
	 */
	public static function erase_keys( $keys ): array {
		$keys = is_array( $keys ) ? $keys : [];

		$keys[ CheckoutContext::META_KEY ] = 'text';

		return $keys;
	}

	/**
	 * An empty anonymised value makes WooCommerce delete the meta.
	 *
	 * @param mixed  $anon_value Anonymised value.
	 * @param string $meta_key   Meta key.
	 * @return mixed
	 */
	public static function erase_value( $anon_value, $meta_key ) {
		return CheckoutContext::META_KEY === $meta_key ? '' : $anon_value;
	}
}

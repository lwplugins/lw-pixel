<?php
/**
 * Barion Pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

use LightweightPlugins\Pixel\Barion\EventMapper;

/**
 * Maps generic events to `bp('track', name, data)` calls. The base pixel
 * (bp.js + pageView) can load without consent, as Barion uses it for fraud
 * prevention; the e-commerce events wait for marketing consent.
 */
final class BarionPixel extends AbstractPixel implements BaseWithoutConsentInterface {

	public function get_id(): string {
		return 'barion';
	}

	public function get_label(): string {
		return __( 'Barion Pixel', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'barion_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'pixel_id', '' );
	}

	public function loads_base_without_consent(): bool {
		return (bool) $this->get_option( 'base_without_consent', true );
	}

	public function get_frontend_config(): array {
		return [
			'pixelId'            => $this->primary_id(),
			'baseWithoutConsent' => $this->loads_base_without_consent(),
		];
	}

	public function map_event( string $event_name, array $params ): ?array {
		$mapped = EventMapper::map( $event_name, $params );

		if ( null === $mapped || 'purchase' !== $mapped['name'] ) {
			return $mapped;
		}

		$email = $this->email_hash( (string) ( $params['order_id'] ?? '' ) );

		if ( '' !== $email ) {
			$mapped['email'] = $email;
		}

		return $mapped;
	}

	/**
	 * SHA-1 of the order's lower-cased billing email, for setEncryptedEmail.
	 * bp.js accepts a 40-character SHA-1 in place of the address.
	 *
	 * @param string $order_id Order id.
	 * @return string
	 */
	private function email_hash( string $order_id ): string {
		if ( ! $this->get_option( 'encrypted_email', true ) || '' === $order_id || ! function_exists( 'wc_get_order' ) ) {
			return '';
		}

		$order = wc_get_order( (int) $order_id );
		$email = $order instanceof \WC_Order ? strtolower( trim( $order->get_billing_email() ) ) : '';
		$hash  = '' !== $email ? sha1( $email ) : '';

		/**
		 * Filter the hashed email given to Barion's setEncryptedEmail.
		 *
		 * @param string $hash     SHA-1 hex hash, or '' to send none.
		 * @param string $order_id Order id.
		 */
		return (string) apply_filters( 'lw_pixel_barion_email_hash', $hash, $order_id );
	}
}

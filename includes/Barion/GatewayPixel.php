<?php
/**
 * Keeps the Barion payment gateway's own pixel off.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Barion;

use LightweightPlugins\Pixel\Pixels\PixelManager;

/**
 * The Barion Payment Gateway plugin (pay-via-barion-for-woocommerce) prints
 * the base pixel snippet in wp_head whenever its own Pixel ID field is
 * filled, without asking for consent. Two copies of the base pixel stop
 * Barion's events, and that copy would also ignore the consent setting here,
 * so it is switched off while LW Pixel's Barion pixel is configured.
 */
final class GatewayPixel {

	/**
	 * Register the gateway's own switch.
	 *
	 * @param PixelManager $pixels Pixel manager.
	 * @return void
	 */
	public static function register( PixelManager $pixels ): void {
		add_filter(
			'woocommerce_barion_disable_tracking',
			static fn ( $disabled ): bool => self::disable( (bool) $disabled, $pixels )
		);
	}

	/**
	 * Whether the gateway's pixel should stay off.
	 *
	 * @param bool         $disabled Current value.
	 * @param PixelManager $pixels   Pixel manager.
	 * @return bool
	 */
	public static function disable( bool $disabled, PixelManager $pixels ): bool {
		$barion = $pixels->get( 'barion' );

		return $disabled || ( null !== $barion && $barion->is_configured() );
	}
}

<?php
/**
 * Tests for the WooCommerce integration's hook surface.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\WooCommerce;

use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\Events\EventManager;
use LightweightPlugins\Pixel\Pixels\PixelManager;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\Pixel\WooCommerce\Integration;

/**
 * @covers \LightweightPlugins\Pixel\WooCommerce\Integration
 */
final class IntegrationTest extends MonkeyTestCase {

	/**
	 * The unauthenticated endpoint returned name/price/SKU of any product id,
	 * including drafts and private products, and nothing used it.
	 */
	public function test_registers_no_add_to_cart_ajax_endpoint(): void {
		new Integration( new EventManager( new PixelManager(), new ConsentManager() ) );

		$this->assertFalse( has_action( 'wp_ajax_nopriv_lw_pixel_add_to_cart' ) );
		$this->assertFalse( has_action( 'wp_ajax_lw_pixel_add_to_cart' ) );
		$this->assertNotFalse( has_action( 'woocommerce_thankyou' ) );
	}
}

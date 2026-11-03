<?php
/**
 * Tests for the Pinterest Tag provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Pixels;

use LightweightPlugins\Pixel\Pixels\PinterestPixel;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Pixels\PinterestPixel
 */
final class PinterestPixelTest extends MonkeyTestCase {

	/**
	 * @dataProvider provide_events
	 *
	 * @param string      $generic  Generic event.
	 * @param string|null $expected Pinterest Tag event, null when not sent.
	 */
	public function test_event_names_are_mapped( string $generic, ?string $expected ): void {
		$mapped = ( new PinterestPixel() )->map_event( $generic, [ 'value' => 10 ] );

		$this->assertSame( $expected, $mapped['name'] ?? null );
	}

	/**
	 * Pinterest's `checkout` is the completed purchase: a started checkout
	 * must not be reported as one (the Conversions API copy agrees).
	 *
	 * @return array<string, array{0: string, 1: string|null}>
	 */
	public static function provide_events(): array {
		return [
			'page view'         => [ 'PageView', 'pagevisit' ],
			'add to cart'       => [ 'AddToCart', 'addtocart' ],
			'purchase'          => [ 'Purchase', 'checkout' ],
			'initiate checkout' => [ 'InitiateCheckout', null ],
		];
	}
}

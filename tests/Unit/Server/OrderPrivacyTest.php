<?php
/**
 * Tests for the WooCommerce privacy export / erasure of the checkout context.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Server\CheckoutContext;
use LightweightPlugins\Pixel\Server\OrderPrivacy;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Server\OrderPrivacy
 */
final class OrderPrivacyTest extends MonkeyTestCase {

	public function test_the_eraser_handles_the_context_and_keeps_core_keys(): void {
		$keys = OrderPrivacy::erase_keys( [ 'Transaction ID' => 'numeric_id' ] );

		$this->assertSame( 'numeric_id', $keys['Transaction ID'] );
		$this->assertSame( 'text', $keys[ CheckoutContext::META_KEY ] );
	}

	/**
	 * An empty anonymised value makes WooCommerce delete the meta instead of
	 * storing a "[deleted]" placeholder.
	 */
	public function test_the_context_is_deleted_not_anonymised(): void {
		$this->assertSame( '', OrderPrivacy::erase_value( '[deleted]', CheckoutContext::META_KEY ) );
		$this->assertSame( '[deleted]', OrderPrivacy::erase_value( '[deleted]', 'Payer first name' ) );
	}

	public function test_the_exporter_lists_the_context_as_text(): void {
		Functions\when( '__' )->returnArg();

		$this->assertArrayHasKey( CheckoutContext::META_KEY, OrderPrivacy::export_keys( [] ) );
		$this->assertSame(
			"consent: granted\nip: 203.0.113.7\nconsents: granted, denied",
			OrderPrivacy::export_value(
				[
					'consent'  => 'granted',
					'ip'       => '203.0.113.7',
					'fbp'      => '',
					'consents' => [ 'fb' => 'granted', 'ga4' => 'denied' ],
				],
				CheckoutContext::META_KEY
			)
		);
	}
}

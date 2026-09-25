<?php
/**
 * Tests for the GA4 Measurement Protocol provider rules.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use LightweightPlugins\Pixel\Server\Providers\Ga4Provider;
use LightweightPlugins\Pixel\Server\RequestContext;

/**
 * @covers \LightweightPlugins\Pixel\Server\Providers\Ga4Provider
 * @covers \LightweightPlugins\Pixel\Server\RequestContext::ga_client_id
 */
final class Ga4ProviderTest extends CapiTestCase {

	private const CONTEXT = [
		'ga'  => '123.456',
		'url' => 'https://shop.test/',
	];

	public function test_purchase_is_sent_with_transaction_id_and_event_id(): void {
		$this->use_options( [ 'ga4_enabled' => true, 'ga4_measurement_id' => 'G-1' ] );

		$built = ( new Ga4Provider() )->build(
			'Purchase',
			[
				'value'    => 10.0,
				'currency' => 'EUR',
				'order_id' => '5',
				'contents' => [ [ 'content_id' => 'A', 'content_name' => 'Hat', 'quantity' => 1, 'price' => 10.0 ] ],
			],
			'order-x-5',
			self::CONTEXT
		);

		$this->assertSame( '123.456', $built['client_id'] );
		$this->assertSame( 'purchase', $built['event']['name'] );
		$this->assertSame( '5', $built['event']['params']['transaction_id'] );
		$this->assertSame( 'order-x-5', $built['event']['params']['event_id'] );
		$this->assertSame( 'A', $built['event']['params']['items'][0]['item_id'] );
	}

	/**
	 * GA4 does not deduplicate MP hits against gtag.js: with LW Pixel's own
	 * GA4 tag in the browser, non-purchase events are not doubled.
	 */
	public function test_other_events_are_skipped_when_the_browser_tag_runs(): void {
		$this->use_options( [ 'ga4_enabled' => true, 'ga4_measurement_id' => 'G-1' ] );

		$this->assertNull( ( new Ga4Provider() )->build( 'Lead', [], 'e', self::CONTEXT ) );
	}

	public function test_other_events_are_sent_when_ga4_is_loaded_elsewhere(): void {
		$this->use_options( [ 'ga4_enabled' => false, 'ga4_measurement_id' => 'G-1' ] );

		$this->assertSame( 'generate_lead', ( new Ga4Provider() )->build( 'Lead', [], 'e', self::CONTEXT )['event']['name'] );
	}

	public function test_nothing_without_a_client_id(): void {
		$this->assertNull( ( new Ga4Provider() )->build( 'Purchase', [], 'e', [ 'ga' => '' ] ) );
	}

	public function test_client_id_parsing(): void {
		$this->assertSame( '1234.5678', RequestContext::ga_client_id( 'GA1.1.1234.5678' ) );
		$this->assertSame( '', RequestContext::ga_client_id( 'garbage' ) );
	}
}

<?php
/**
 * Tests for the per-provider server-side Purchase.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Server\CheckoutContext;
use LightweightPlugins\Pixel\Server\OrderEnrich;
use LightweightPlugins\Pixel\Server\ServerPurchase;

/**
 * @covers \LightweightPlugins\Pixel\Server\ServerPurchase
 * @covers \LightweightPlugins\Pixel\Server\OrderEnrich
 * @covers \LightweightPlugins\Pixel\Server\CheckoutContext
 */
final class ServerPurchaseTest extends CapiTestCase {

	private const CONTEXT = [
		'consent'  => CheckoutContext::GRANTED,
		'consents' => [
			'fb'      => CheckoutContext::GRANTED,
			'chatgpt' => CheckoutContext::GRANTED,
			'ga4'     => CheckoutContext::DENIED,
		],
		'ip'       => '203.0.113.7',
		'ua'       => 'CustomerBrowser/1.0',
		'fbp'      => 'fb.1.1.x',
		'obref'    => 'ob.1',
		'url'      => 'https://shop.test/checkout/',
	];

	/**
	 * Decoded bodies keyed by endpoint host.
	 *
	 * @var array<int, string>
	 */
	private array $urls = [];

	protected function setUp(): void {
		parent::setUp();
		$this->urls = [];
		$this->use_options(
			[
				'chatgpt_capi_enabled' => true,
				'chatgpt_pixel_id'     => 'px_1',
				'chatgpt_api_key'      => 'sk',
				'ga4_mp_enabled'       => true,
				'ga4_measurement_id'   => 'G-1',
				'ga4_mp_api_secret'    => 's',
			]
		);
		Functions\when( 'add_query_arg' )->alias(
			static fn ( $key, $value = null, $url = '' ): string => is_array( $key ) ? $value . '?' . http_build_query( $key ) : $url . '?' . $key . '=' . $value
		);
		Functions\when( 'wp_remote_post' )->alias(
			function ( $url, $args ): array {
				$this->urls[] = (string) $url;
				$this->sent[] = (array) json_decode( (string) $args['body'], true );
				return [];
			}
		);
	}

	public function test_sends_to_each_consented_provider_with_the_order_event_id(): void {
		$order = $this->order( [ CheckoutContext::META_KEY => self::CONTEXT ] );
		$order->shouldReceive( 'update_meta_data' )->once()->with( '_lw_pixel_capi_purchase_tracked', '1' );
		$order->shouldReceive( 'update_meta_data' )->once()->with( '_lw_pixel_server_purchase_chatgpt', '1' );
		Functions\when( 'wc_get_order' )->justReturn( $order );

		ServerPurchase::send( 42 );

		$this->assertCount( 2, $this->sent, 'GA4 was refused at checkout.' );
		$meta    = $this->sent[0]['data'][0];
		$chatgpt = $this->sent[1]['events'][0];
		$this->assertSame( $meta['event_id'], $chatgpt['id'] );
		$this->assertStringStartsWith( 'order-', $chatgpt['id'] );
		$this->assertSame( 'order_created', $chatgpt['type'] );
		$this->assertSame( 9900, $chatgpt['data']['amount'], 'HUF 99 in ISO minor units.' );
		$this->assertStringContainsString( 'bzr.openai.com', $this->urls[1] );
	}

	/**
	 * Meta already accepted it; only the failed provider is retried.
	 */
	public function test_skips_providers_already_marked(): void {
		Functions\when( 'wc_get_order' )->justReturn(
			$this->order(
				[
					CheckoutContext::META_KEY          => self::CONTEXT,
					'_lw_pixel_capi_purchase_tracked' => '1',
				]
			)
		);

		ServerPurchase::send( 42 );

		$this->assertCount( 1, $this->sent );
		$this->assertArrayHasKey( 'events', $this->sent[0] );
	}

	public function test_legacy_context_only_allows_meta(): void {
		Functions\when( 'wc_get_order' )->justReturn(
			$this->order(
				[
					CheckoutContext::META_KEY => [
						'consent' => CheckoutContext::GRANTED,
						'ua'      => 'Old/1.0',
						'url'     => 'https://shop.test/checkout/',
					],
				]
			)
		);

		ServerPurchase::send( 42 );

		$this->assertCount( 1, $this->sent );
		$this->assertArrayHasKey( 'data', $this->sent[0] );
	}

	public function test_meta_without_order_enrich_sends_on_the_thankyou_page(): void {
		$this->use_options(
			[
				'fb_order_enrich'      => false,
				'chatgpt_capi_enabled' => true,
				'chatgpt_pixel_id'     => 'px_1',
				'chatgpt_api_key'      => 'sk',
			]
		);

		$this->assertSame( [ 'chatgpt' ], OrderEnrich::providers_for( OrderEnrich::TRIGGER_STATUS ) );
		$this->assertSame( [ 'fb' ], OrderEnrich::providers_for( OrderEnrich::TRIGGER_THANKYOU ) );
	}

	public function test_thankyou_trigger_is_queued_separately(): void {
		$this->use_options( [ 'fb_order_enrich' => false ] );
		Functions\expect( 'as_enqueue_async_action' )
			->once()
			->with( OrderEnrich::ASYNC_HOOK, [ 42, OrderEnrich::TRIGGER_THANKYOU ], 'lw-pixel', true )
			->andReturn( 3 );

		OrderEnrich::thankyou( 42 );

		$this->assertSame( [], $this->sent );
	}
}

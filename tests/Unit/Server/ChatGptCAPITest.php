<?php
/**
 * Tests for the ChatGPT Ads Conversions API client and provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Server\ChatGptCAPI;
use LightweightPlugins\Pixel\Server\Providers\ChatGptProvider;

/**
 * @covers \LightweightPlugins\Pixel\Server\ChatGptCAPI
 * @covers \LightweightPlugins\Pixel\Server\Providers\ChatGptProvider
 */
final class ChatGptCAPITest extends CapiTestCase {

	/**
	 * Requests: url + headers + decoded body.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $requests = [];

	protected function setUp(): void {
		parent::setUp();
		$this->requests = [];
		$this->use_options(
			[
				'chatgpt_enabled'      => true,
				'chatgpt_capi_enabled' => true,
				'chatgpt_pixel_id'     => 'px_123',
				'chatgpt_api_key'      => 'sk-secret',
			]
		);
		Functions\when( 'add_query_arg' )->alias( static fn ( $key, $value, $url ): string => $url . '?' . $key . '=' . $value );
		Functions\when( '__' )->returnArg();
		Functions\when( 'wp_remote_post' )->alias(
			function ( $url, $args ): array {
				$this->requests[] = [
					'url'     => $url,
					'headers' => $args['headers'],
					'body'    => (array) json_decode( (string) $args['body'], true ),
				];
				return [];
			}
		);
	}

	public function test_send_posts_to_the_pixel_endpoint_with_bearer_auth(): void {
		$result = ChatGptCAPI::send( [ [ 'id' => 'e1' ] ] );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 'https://bzr.openai.com/v1/events?pid=px_123', $this->requests[0]['url'] );
		$this->assertSame( 'Bearer sk-secret', $this->requests[0]['headers']['Authorization'] );
		$this->assertSame( 'lw-pixel', $this->requests[0]['body']['integration_source'] );
		$this->assertArrayNotHasKey( 'validate_only', $this->requests[0]['body'] );
	}

	public function test_connection_test_uses_validate_only_and_never_returns_the_key(): void {
		$result = ChatGptCAPI::test_connection();

		$this->assertTrue( $result['ok'] );
		$this->assertTrue( $this->requests[0]['body']['validate_only'] );
		$this->assertSame( 'page_viewed', $this->requests[0]['body']['events'][0]['type'] );
		$this->assertStringNotContainsString( 'sk-secret', (string) json_encode( $result ) );
	}

	public function test_connection_test_reports_a_rejected_key(): void {
		$this->status = 401;

		$result = ChatGptCAPI::test_connection();

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 401, $result['status'] );
		$this->assertStringContainsString( 'API key', $result['message'] );
	}

	public function test_nothing_is_sent_without_a_key(): void {
		$this->use_options( [ 'chatgpt_pixel_id' => 'px_123' ] );

		$this->assertFalse( ChatGptCAPI::send( [ [ 'id' => 'e1' ] ] )['ok'] );
		$this->assertFalse( ChatGptCAPI::test_connection()['ok'] );
		$this->assertSame( [], $this->requests );
	}

	public function test_provider_builds_a_deduplicable_order_event(): void {
		$event = ( new ChatGptProvider() )->build(
			'Purchase',
			[
				'value'    => 12.5,
				'currency' => 'EUR',
				'order_id' => '9',
				'contents' => [],
			],
			'order-abc-9',
			[
				'time'     => 1_700_000_000,
				'url'      => 'https://shop.test/checkout/',
				'ip'       => '203.0.113.7',
				'ua'       => 'Browser/1.0',
				'obref'    => 'ob.1',
				'oppref'   => 'op.2',
				'customer' => [ 'email' => 'a@b.co' ],
			]
		);

		$this->assertSame( 'order-abc-9', $event['id'] );
		$this->assertSame( 'order_created', $event['type'] );
		$this->assertSame( 1_700_000_000_000, $event['timestamp_ms'] );
		$this->assertSame( 'web', $event['action_source'] );
		$this->assertSame( 'op.2', $event['oppref'] );
		$this->assertSame( [ 'type' => 'contents', 'amount' => 1250, 'currency' => 'EUR' ], $event['data'] );
		$this->assertSame(
			[
				'ip_address' => '203.0.113.7',
				'user_agent' => 'Browser/1.0',
				'obref'      => 'ob.1',
			],
			$event['user'],
			'Hashed customer data only with advanced matching on.'
		);
	}

	public function test_provider_adds_hashed_customer_data_with_advanced_matching(): void {
		$this->use_options(
			[
				'chatgpt_capi_enabled'      => true,
				'chatgpt_advanced_matching' => true,
			]
		);

		$user = ChatGptProvider::user( [ 'customer' => [ 'email' => ' A@B.co ' ] ] );

		$this->assertSame( [ hash( 'sha256', 'a@b.co' ) ], $user['emails_sha256'] );
	}

	public function test_provider_skips_events_without_an_absolute_source_url(): void {
		$this->assertNull( ( new ChatGptProvider() )->build( 'PageView', [], 'e', [ 'url' => '/relative' ] ) );
	}

	/**
	 * Separate process: the constant would leak into every later test.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_constant_api_key_wins(): void {
		define( 'LW_PIXEL_CHATGPT_API_KEY', 'sk-from-config' );

		$this->assertSame( 'sk-from-config', ChatGptCAPI::api_key() );
	}
}

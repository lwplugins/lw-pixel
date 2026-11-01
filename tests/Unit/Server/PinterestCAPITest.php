<?php
/**
 * Tests for the Pinterest Conversions API client and provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Compliance\LduMode;
use LightweightPlugins\Pixel\Compliance\MedicalMode;
use LightweightPlugins\Pixel\Server\DispatchQueue;
use LightweightPlugins\Pixel\Server\EventDispatcher;
use LightweightPlugins\Pixel\Server\PinterestCAPI;
use LightweightPlugins\Pixel\Server\Providers\PinterestProvider;

/**
 * @covers \LightweightPlugins\Pixel\Server\PinterestCAPI
 * @covers \LightweightPlugins\Pixel\Server\Providers\PinterestProvider
 * @covers \LightweightPlugins\Pixel\Server\Providers\PinterestCustomData
 */
final class PinterestCAPITest extends CapiTestCase {

	private const CONTEXT = [
		'time'     => 1_700_000_000,
		'url'      => 'https://shop.test/product/mug/',
		'ip'       => '203.0.113.7',
		'ua'       => 'Browser/1.0',
		'epik'     => 'dj0yJnU9abc',
		'customer' => [
			'email'        => ' Buyer@Example.com ',
			'phone'        => '06 30 123 4567',
			'calling_code' => '36',
			'first_name'   => 'Éva',
			'last_name'    => 'Kiss-Nagy',
			'city'         => 'Szent Endre',
			'region'       => 'PE',
			'postal_code'  => 'H-2000',
			'country'      => 'HU',
			'external_id'  => '17',
		],
	];

	/**
	 * Requests: url + headers + decoded body.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $requests = [];

	protected function setUp(): void {
		parent::setUp();
		$this->requests = [];
		$this->use_options( self::options() );
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

	protected function tearDown(): void {
		DispatchQueue::reset();
		parent::tearDown();
	}

	/**
	 * Pinterest CAPI options (merged over the harness's Meta options).
	 *
	 * @param array<string, mixed> $extra Extra options.
	 * @return array<string, mixed>
	 */
	private static function options( array $extra = [] ): array {
		return array_merge(
			[
				'pinterest_capi_enabled'  => true,
				'pinterest_ad_account_id' => '549755885175',
				'pinterest_capi_token'    => 'pina_secret',
			],
			$extra
		);
	}

	public function test_send_posts_a_data_batch_with_bearer_auth(): void {
		$result = PinterestCAPI::send( [ [ 'event_id' => 'e1' ] ] );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 'https://api.pinterest.com/v5/ad_accounts/549755885175/events', $this->requests[0]['url'] );
		$this->assertSame( 'Bearer pina_secret', $this->requests[0]['headers']['Authorization'] );
		$this->assertSame( 'application/json', $this->requests[0]['headers']['Content-Type'] );
		$this->assertSame( [ 'data' => [ [ 'event_id' => 'e1' ] ] ], $this->requests[0]['body'] );
	}

	public function test_test_mode_adds_test_true_to_the_url(): void {
		$this->use_options( self::options( [ 'pinterest_capi_test' => true ] ) );

		PinterestCAPI::send( [ [ 'event_id' => 'e1' ] ] );

		$this->assertSame( 'https://api.pinterest.com/v5/ad_accounts/549755885175/events?test=true', $this->requests[0]['url'] );
	}

	public function test_partially_rejected_batch_is_not_ok(): void {
		$this->status = 422;

		$result = PinterestCAPI::send( [ [ 'event_id' => 'e1' ] ] );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 422, $result['status'] );
	}

	public function test_connection_test_uses_test_mode_and_never_returns_the_token(): void {
		$result = PinterestCAPI::test_connection();

		$this->assertTrue( $result['ok'] );
		$this->assertStringEndsWith( '?test=true', $this->requests[0]['url'] );
		$this->assertSame( 'page_visit', $this->requests[0]['body']['data'][0]['event_name'] );
		$this->assertStringNotContainsString( 'pina_secret', (string) json_encode( $result ) );
	}

	public function test_connection_test_reports_a_rejected_token(): void {
		$this->status = 401;

		$result = PinterestCAPI::test_connection();

		$this->assertFalse( $result['ok'] );
		$this->assertStringContainsString( 'access token', $result['message'] );
	}

	public function test_nothing_is_sent_without_an_ad_account(): void {
		$this->use_options( self::options( [ 'pinterest_ad_account_id' => '' ] ) );

		$this->assertFalse( PinterestCAPI::send( [ [ 'event_id' => 'e1' ] ] )['ok'] );
		$this->assertFalse( PinterestCAPI::test_connection()['ok'] );
		$this->assertFalse( ( new PinterestProvider() )->is_active() );
		$this->assertSame( [], $this->requests );
	}

	/**
	 * @dataProvider provide_event_names
	 *
	 * @param string      $generic  Generic event.
	 * @param string|null $expected Pinterest event, null when not sent.
	 */
	public function test_event_names_are_mapped( string $generic, ?string $expected ): void {
		$event = ( new PinterestProvider() )->build( $generic, [], 'evt', self::CONTEXT );

		$this->assertSame( $expected, $event['event_name'] ?? null );
	}

	/**
	 * Generic → Pinterest event names.
	 *
	 * @return array<string, array{0: string, 1: string|null}>
	 */
	public static function provide_event_names(): array {
		return [
			'page view'           => [ 'PageView', 'page_visit' ],
			'view content'        => [ 'ViewContent', 'page_visit' ],
			'add to cart'         => [ 'AddToCart', 'add_to_cart' ],
			'purchase'            => [ 'Purchase', 'checkout' ],
			'lead'                => [ 'Lead', 'lead' ],
			'contact'             => [ 'Contact', 'lead' ],
			'search'              => [ 'Search', 'search' ],
			'registration'        => [ 'CompleteRegistration', 'signup' ],
			'signup'              => [ 'Signup', 'signup' ],
			'view category'       => [ 'ViewCategory', 'view_category' ],
			'checkout not a sale' => [ 'InitiateCheckout', null ],
			'payment info'        => [ 'AddPaymentInfo', null ],
			'custom event'        => [ 'Login', null ],
		];
	}

	public function test_event_carries_the_shared_id_and_request_identifiers(): void {
		$event = ( new PinterestProvider() )->build( 'PageView', [], 'evt-1', self::CONTEXT );

		$this->assertSame( 'evt-1', $event['event_id'] );
		$this->assertSame( 'web', $event['action_source'] );
		$this->assertSame( 1_700_000_000, $event['event_time'] );
		$this->assertSame( 'https://shop.test/product/mug/', $event['event_source_url'] );
		$this->assertSame(
			[
				'client_ip_address' => '203.0.113.7',
				'client_user_agent' => 'Browser/1.0',
				'click_id'          => 'dj0yJnU9abc',
			],
			$event['user_data'],
			'Hashed customer data only with advanced matching on.'
		);
		$this->assertArrayNotHasKey( 'custom_data', $event );
	}

	public function test_advanced_matching_adds_hashed_arrays_normalised_per_pinterest(): void {
		$this->use_options( self::options( [ 'pinterest_advanced_matching' => true ] ) );

		$data = PinterestProvider::user_data( self::CONTEXT );

		$this->assertSame( [ hash( 'sha256', 'buyer@example.com' ) ], $data['em'] );
		$this->assertSame( [ hash( 'sha256', '36301234567' ) ], $data['ph'] );
		$this->assertSame( [ hash( 'sha256', 'éva' ) ], $data['fn'] );
		$this->assertSame( [ hash( 'sha256', 'kissnagy' ) ], $data['ln'] );
		$this->assertSame( [ hash( 'sha256', 'szentendre' ) ], $data['ct'] );
		$this->assertSame( [ hash( 'sha256', 'pe' ) ], $data['st'] );
		$this->assertSame( [ hash( 'sha256', '2000' ) ], $data['zp'] );
		$this->assertSame( [ hash( 'sha256', 'hu' ) ], $data['country'] );
		$this->assertSame( [ hash( 'sha256', '17' ) ], $data['external_id'] );
	}

	public function test_purchase_sends_string_values_contents_and_order_id(): void {
		$event = ( new PinterestProvider() )->build(
			'Purchase',
			[
				'value'     => 25.5,
				'currency'  => 'HUF',
				'order_id'  => '42',
				'num_items' => 2,
				'contents'  => [
					[
						'content_id'   => 'SKU-1',
						'content_name' => 'Mug',
						'price'        => 10.0,
						'quantity'     => 2,
					],
					[
						'content_id' => 'SKU-2',
						'price'      => 5.5,
					],
				],
			],
			'order-abc-42',
			self::CONTEXT
		);

		$this->assertSame(
			[
				'content_ids' => [ 'SKU-1', 'SKU-2' ],
				'contents'    => [
					[
						'id'         => 'SKU-1',
						'item_price' => '10',
						'quantity'   => 2,
						'item_name'  => 'Mug',
					],
					[
						'id'         => 'SKU-2',
						'item_price' => '5.5',
						'quantity'   => 1,
					],
				],
				'value'       => '25.5',
				'currency'    => 'HUF',
				'num_items'   => 2,
				'order_id'    => '42',
			],
			$event['custom_data']
		);
	}

	public function test_free_purchase_still_sends_a_zero_value(): void {
		$event = ( new PinterestProvider() )->build(
			'Purchase',
			[
				'value'    => 0,
				'currency' => 'EUR',
				'order_id' => '9',
			],
			'e',
			self::CONTEXT
		);

		$this->assertSame( '0', $event['custom_data']['value'] );
	}

	public function test_single_product_value_is_price_times_quantity(): void {
		$event = ( new PinterestProvider() )->build(
			'AddToCart',
			[
				'content_id'   => '7',
				'content_name' => 'Mug',
				'price'        => 3.3,
				'quantity'     => 3,
				'currency'     => 'EUR',
			],
			'e',
			self::CONTEXT
		);

		$this->assertSame( '9.9', $event['custom_data']['value'] );
		$this->assertSame( 'Mug', $event['custom_data']['content_name'] );
		$this->assertSame( [ '7' ], $event['custom_data']['content_ids'] );
	}

	public function test_search_and_category_details_are_sent(): void {
		$provider = new PinterestProvider();

		$search   = $provider->build( 'Search', [ 'search_string' => 'mug' ], 'e', self::CONTEXT );
		$category = $provider->build( 'ViewCategory', [ 'content_category' => 'Kitchen' ], 'e', self::CONTEXT );

		$this->assertSame( [ 'search_string' => 'mug' ], $search['custom_data'] );
		$this->assertSame( [ 'content_category' => 'Kitchen' ], $category['custom_data'] );
	}

	public function test_event_without_ip_and_user_agent_or_email_is_not_sent(): void {
		$this->assertNull( ( new PinterestProvider() )->build( 'Lead', [], 'e', [ 'epik' => 'x' ] ) );
	}

	public function test_medical_mode_strips_identifiers_so_nothing_is_sent(): void {
		Filters\expectApplied( 'lw_pixel_pinterest_capi_event' )->once()->andReturnUsing(
			static fn ( array $event ): array => MedicalMode::strip_pinterest_event( $event )
		);

		$this->assertNull( ( new PinterestProvider() )->build( 'Search', [ 'search_string' => 'x' ], 'e', self::CONTEXT ) );
	}

	public function test_medical_mode_strips_names_and_search_terms(): void {
		$event = MedicalMode::strip_pinterest_event(
			[
				'user_data'   => [
					'client_ip_address' => '203.0.113.7',
					'em'                => [ 'h' ],
					'click_id'          => 'c',
				],
				'custom_data' => [
					'content_name'  => 'Clinic',
					'search_string' => 'x',
					'contents'      => [
						[
							'id'        => '1',
							'item_name' => 'Test',
						],
					],
				],
			]
		);

		$this->assertSame( [ 'click_id' => 'c' ], $event['user_data'] );
		$this->assertSame( [ 'contents' => [ [ 'id' => '1' ] ] ], $event['custom_data'] );
	}

	public function test_ldu_marks_the_event_as_limited_data_processing(): void {
		Filters\expectApplied( 'lw_pixel_pinterest_capi_event' )->once()->andReturnUsing(
			static fn ( array $event ): array => LduMode::attach_pinterest_ldp( $event )
		);

		$event = ( new PinterestProvider() )->build( 'PageView', [], 'e', self::CONTEXT );

		$this->assertSame( 'LDP', $event['custom_data']['opt_out_type'] );
	}

	public function test_server_copy_respects_the_pinterest_consent_category(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_get_current_user' )->justReturn( new class() { public function exists(): bool { return false; } } ); // phpcs:ignore
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'wp_doing_cron' )->justReturn( false );
		Functions\when( 'has_filter' )->justReturn( 10 );
		Filters\expectApplied( 'lw_cookie_is_category_allowed' )->andReturnUsing(
			static fn ( $allowed, $category ): bool => 'analytics' === $category
		);
		$this->use_options(
			self::options(
				[
					'consent_marketing_pixels' => [ 'pinterest' ],
					'consent_analytics_pixels' => [ 'fb' ],
				]
			)
		);
		$_SERVER['REMOTE_ADDR']     = '203.0.113.7';
		$_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';
		$_SERVER['REQUEST_URI']     = '/cart/';

		$this->assertTrue( EventDispatcher::capture( 'Lead', [], 'evt-c', EventDispatcher::SCOPE_VISITOR ) );

		$pending = DispatchQueue::pending();
		$this->assertArrayHasKey( 'fb', $pending );
		$this->assertArrayNotHasKey( 'pinterest', $pending, 'No marketing consent: no Pinterest copy.' );
	}

	/**
	 * Separate process: the constant would leak into every later test.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_constant_token_wins(): void {
		define( 'LW_PIXEL_PINTEREST_TOKEN', 'pina_from_config' );

		$this->assertSame( 'pina_from_config', PinterestCAPI::token() );
	}
}

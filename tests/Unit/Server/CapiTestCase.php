<?php
/**
 * Shared harness for Conversion API tests.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Server;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;
use Mockery;

/**
 * Stubs options, the HTTP layer and a WC order so CAPI code runs without WordPress.
 */
abstract class CapiTestCase extends MonkeyTestCase {

	/**
	 * Decoded bodies of every wp_remote_post() call.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $sent = [];

	/**
	 * HTTP status code the fake Graph API answers with.
	 *
	 * @var int
	 */
	protected int $status = 200;

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		$this->sent   = [];
		$this->status = 200;

		$this->use_options( [] );
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'wp_json_encode' )->alias( static fn ( $data ): string => (string) json_encode( $data ) );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->alias( fn (): int => $this->status );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '{}' );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'home_url' )->alias( static fn ( $path = '' ): string => 'https://shop.test' . $path );
		Functions\when( 'wc_get_checkout_url' )->justReturn( 'https://shop.test/checkout/' );
		$GLOBALS['wpdb'] = $this->wpdb( '1' );
		Functions\when( 'wp_remote_post' )->alias(
			function ( $url, $args ): array {
				unset( $url );
				$this->sent[] = (array) json_decode( (string) $args['body'], true );
				return [];
			}
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		Options::clear_cache();
		$_SERVER = array_diff_key( $_SERVER, array_flip( [ 'REMOTE_ADDR', 'HTTP_USER_AGENT', 'REQUEST_URI' ] ) );
		$_COOKIE = [];
		parent::tearDown();
	}

	/**
	 * Use the given saved options (merged over defaults by Options).
	 *
	 * @param array<string, mixed> $options Saved options.
	 * @return void
	 */
	protected function use_options( array $options ): void {
		Options::clear_cache();
		Functions\when( 'get_option' )->justReturn(
			array_merge(
				[
					'fb_capi_enabled' => true,
					'fb_order_enrich' => true,
					'fb_pixel_id'     => '123',
					'fb_capi_token'   => 'token',
				],
				$options
			)
		);
	}

	/**
	 * A $wpdb double whose GET_LOCK answers with the given value.
	 *
	 * @param string $lock_result '1' = acquired, '0' = held elsewhere.
	 * @return object
	 */
	protected function wpdb( string $lock_result ): object {
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing(
			static fn ( $query, ...$args ): string => vsprintf( str_replace( '%s', "'%s'", (string) $query ), $args )
		);
		$wpdb->shouldReceive( 'get_var' )->andReturn( $lock_result );
		$wpdb->shouldReceive( 'query' )->andReturn( 1 );

		return $wpdb;
	}

	/**
	 * A guest WC order mock with the given order meta.
	 *
	 * @param array<string, mixed> $meta Order meta.
	 * @return \Mockery\MockInterface
	 */
	protected function order( array $meta = [] ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_meta' )->andReturnUsing(
			static fn ( $key ) => $meta[ $key ] ?? ''
		);
		$order->shouldReceive( 'update_meta_data' )->byDefault();
		$order->shouldReceive( 'save' )->byDefault();
		$order->shouldReceive( 'get_items' )->andReturn( [] );
		$order->shouldReceive( 'get_total' )->andReturn( 99.0 );
		$order->shouldReceive( 'get_subtotal' )->andReturn( 80.0 );
		$order->shouldReceive( 'get_currency' )->andReturn( 'HUF' );
		$order->shouldReceive( 'get_id' )->andReturn( 42 );
		$order->shouldReceive( 'get_customer_id' )->andReturn( 0 );
		$order->shouldReceive( 'get_billing_email' )->andReturn( 'buyer@example.com' );
		foreach ( [ 'phone', 'first_name', 'last_name', 'city', 'state', 'postcode', 'country' ] as $field ) {
			$order->shouldReceive( 'get_billing_' . $field )->andReturn( '' );
		}

		return $order;
	}
}

<?php
/**
 * Tests for the pending-event store's visitor keying.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Events;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Events\PendingEventStore;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Events\PendingEventStore
 */
final class PendingEventStoreTest extends MonkeyTestCase {

	private const TOKEN_A = '0123456789abcdef0123456789abcdef';
	private const TOKEN_B = 'fedcba9876543210fedcba9876543210';

	/**
	 * Fake transient storage.
	 *
	 * @var array<string, mixed>
	 */
	private array $transients = [];

	protected function setUp(): void {
		parent::setUp();
		PendingEventStore::reset();
		Options::clear_cache();
		$this->transients = [];

		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'sanitize_key' )->alias( static fn ( $key ): string => strtolower( (string) preg_replace( '/[^a-z0-9_\-]/i', '', (string) $key ) ) );
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'is_ssl' )->justReturn( true );
		Functions\when( 'get_transient' )->alias( fn ( $key ) => $this->transients[ $key ] ?? false );
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value ): bool {
				$this->transients[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_transient' )->alias(
			function ( $key ): bool {
				unset( $this->transients[ $key ] );
				return true;
			}
		);
	}

	protected function tearDown(): void {
		PendingEventStore::reset();
		Options::clear_cache();
		$_COOKIE  = [];
		$_SERVER = array_diff_key( $_SERVER, array_flip( [ 'REMOTE_ADDR', 'HTTP_USER_AGENT' ] ) );
		parent::tearDown();
	}

	/**
	 * Two visitors behind the same NAT/CDN (same IP + UA) must not share a
	 * key, or one visitor's pending event pops on the other's page.
	 */
	public function test_visitors_sharing_ip_and_user_agent_get_different_keys(): void {
		$_SERVER['REMOTE_ADDR']     = '203.0.113.7';
		$_SERVER['HTTP_USER_AGENT'] = 'SameBrowser/1.0';

		$_COOKIE[ PendingEventStore::COOKIE ] = self::TOKEN_A;
		$first                                = PendingEventStore::current_owner();
		$_COOKIE[ PendingEventStore::COOKIE ] = self::TOKEN_B;
		$second                               = PendingEventStore::current_owner();

		$this->assertNotSame( $first, $second );
	}

	public function test_anonymous_visitor_without_a_token_has_nothing_pending(): void {
		$this->transients['lw_pixel_pending_a_'] = [ [ 'name' => 'Lead', 'params' => [] ] ];

		$this->assertSame( '', PendingEventStore::current_owner() );
		$this->assertSame( [], PendingEventStore::pop( PendingEventStore::current_owner() ) );
	}

	public function test_malformed_cookie_token_is_ignored(): void {
		$_COOKIE[ PendingEventStore::COOKIE ] = '../../etc';

		$this->assertSame( '', PendingEventStore::current_owner() );
	}

	public function test_logged_in_user_is_keyed_by_user_id(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 5 );

		$this->assertSame( 'u_5', PendingEventStore::current_owner() );
	}

	public function test_event_pushed_for_a_new_visitor_pops_under_the_minted_token(): void {
		PendingEventStore::push_for_current_visitor( 'Lead', [ 'form_id' => '3' ] );

		$owner = PendingEventStore::current_owner();

		$this->assertMatchesRegularExpression( '/^a_[a-f0-9]{32}$/', $owner );
		$this->assertSame( [ [ 'name' => 'Lead', 'params' => [ 'form_id' => '3' ] ] ], PendingEventStore::pop( $owner ) );
		$this->assertSame( [], PendingEventStore::pop( $owner ) );
	}

	/**
	 * With LW Cookie active and every tracking category refused, no token
	 * cookie is set and nothing is stored.
	 */
	public function test_anonymous_visitor_without_consent_is_not_stored(): void {
		add_filter( 'lw_cookie_is_category_allowed', '__return_false', 10, 2 );

		PendingEventStore::push_for_current_visitor( 'Lead', [] );

		$this->assertSame( '', PendingEventStore::current_owner() );
		$this->assertSame( [], $this->transients );
	}
}

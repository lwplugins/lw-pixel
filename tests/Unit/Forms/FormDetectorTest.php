<?php
/**
 * Tests for Lead delivery from form submissions.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Forms;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Events\PendingEventStore;
use LightweightPlugins\Pixel\Forms\FormDetector;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Forms\FormDetector
 */
final class FormDetectorTest extends MonkeyTestCase {

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
		Functions\when( 'is_ssl' )->justReturn( true );
		Functions\when( 'get_transient' )->alias( fn ( $key ) => $this->transients[ $key ] ?? false );
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value ): bool {
				$this->transients[ $key ] = $value;
				return true;
			}
		);
	}

	protected function tearDown(): void {
		PendingEventStore::reset();
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * CF7 submits over REST: no page is rendered in that request, so the
	 * Lead must be persisted for the visitor's next page view instead of
	 * being queued into a data island that is never printed.
	 */
	public function test_ajax_form_submission_persists_the_lead_for_the_next_page_view(): void {
		$form = new class() {
			public function id(): int {
				return 7;
			}

			public function title(): string {
				return 'Contact';
			}
		};

		( new FormDetector() )->on_cf7( $form );

		$pending = array_values( $this->transients );
		$this->assertCount( 1, $pending );
		$this->assertSame( 'Lead', $pending[0][0]['name'] );
		$this->assertSame( '7', $pending[0][0]['params']['form_id'] );
		$this->assertSame( 'Contact', $pending[0][0]['params']['form_name'] );
		$this->assertSame( 'cf7', $pending[0][0]['params']['source'] );
	}
}

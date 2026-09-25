<?php
/**
 * Tests for the settings import REST controller.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Rest\Admin;

use Brain\Monkey\Filters;
use LightweightPlugins\Pixel\Rest\Admin\MigratorController;
use LightweightPlugins\Pixel\Tools\Migrators\MigratorInterface;
use WP_Error;
use WP_REST_Request;

/**
 * @covers \LightweightPlugins\Pixel\Rest\Admin\MigratorController
 */
final class MigratorControllerTest extends RestTestCase {

	/**
	 * Register one fake importer.
	 *
	 * @param bool $available Whether its source is detected.
	 * @return void
	 */
	private function register( bool $available ): void {
		$fake = new class( $available ) implements MigratorInterface {
			public function __construct( private bool $available ) {}

			public function get_id(): string {
				return 'fake';
			}

			public function get_label(): string {
				return 'Fake source';
			}

			public function is_available(): bool {
				return $this->available;
			}

			public function preview(): array {
				return [
					'fb_pixel_id'   => [
						'from' => '',
						'to'   => '123456',
					],
					'fb_capi_token' => [
						'from' => '',
						'to'   => 'EAABsecret',
					],
				];
			}

			public function run(): array {
				return [
					'updated' => [ 'fb_pixel_id' ],
					'skipped' => [ 'pys.x' ],
				];
			}
		};

		Filters\expectApplied( 'lw_pixel_migrators' )->andReturn( [ 'fake' => $fake ] );
	}

	public function test_the_preview_never_shows_a_secret(): void {
		$this->register( true );

		$rows = ( new MigratorController() )->list_migrators()->get_data()['migrators'][0]['preview'];

		$this->assertSame( '123456', $rows[0]['to'] );
		$this->assertTrue( $rows[1]['secret'] );
		$this->assertTrue( $rows[1]['to'] );
	}

	public function test_running_an_undetected_source_is_refused(): void {
		$this->register( false );
		$request = new WP_REST_Request( [ 'id' => 'fake' ] );

		$result = ( new MigratorController() )->run_migrator( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_running_returns_the_updated_keys(): void {
		$this->register( true );

		$data = ( new MigratorController() )->run_migrator( new WP_REST_Request( [ 'id' => 'fake' ] ) )->get_data();

		$this->assertSame( [ 'fb_pixel_id' ], $data['updated'] );
	}
}

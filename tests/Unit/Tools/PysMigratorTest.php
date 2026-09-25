<?php
/**
 * Tests for the settings importer's write path.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Tools;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\Pixel\Tools\Migrators\PysMigrator;

/**
 * @covers \LightweightPlugins\Pixel\Tools\Migrators\PysMigrator
 */
final class PysMigratorTest extends MonkeyTestCase {

	/**
	 * Last value written with update_option().
	 *
	 * @var array<string, mixed>
	 */
	private array $saved = [];

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		unset( $GLOBALS['wpdb'] );
		$this->saved = [];

		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'sanitize_text_field' )->alias( static fn ( $value ): string => trim( strip_tags( (string) $value ) ) );
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'update_option' )->alias(
			function ( $name, $value ): bool {
				unset( $name );
				$this->saved = $value;
				return true;
			}
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Use the given stored lw-pixel options and source buckets.
	 *
	 * @param array<string, mixed>                $lw      lw_pixel_options.
	 * @param array<string, array<string, mixed>> $buckets Source buckets.
	 * @return void
	 */
	private function source( array $lw, array $buckets ): void {
		Functions\when( 'get_option' )->alias(
			static fn ( $name, $fallback = false ) => Options::OPTION_NAME === $name ? $lw : ( $buckets[ $name ] ?? $fallback )
		);
	}

	public function test_blank_source_value_never_overwrites_a_configured_id(): void {
		$this->source(
			[ 'gtm_container_id' => 'GTM-EXIST' ],
			[ 'pys_gtm' => [ 'gtm_id' => '', 'container_id' => '' ] ]
		);

		( new PysMigrator() )->run();

		$this->assertSame( 'GTM-EXIST', $this->saved['gtm_container_id'] );
	}

	/**
	 * tag_id and uet_id both map to bing_tag_id: a blank later alias must
	 * not replace the earlier non-empty one.
	 */
	public function test_first_non_empty_alias_wins(): void {
		$this->source( [], [ 'pys_bing' => [ 'tag_id' => '1234', 'uet_id' => '' ] ] );

		$result = ( new PysMigrator() )->run();

		$this->assertSame( '1234', $this->saved['bing_tag_id'] );
		$this->assertContains( 'bing_tag_id', $result['updated'] );
	}

	public function test_imported_values_go_through_the_settings_sanitizer(): void {
		$this->source( [], [ 'pys_facebook' => [ 'pixel_id' => '<b>999</b>' ] ] );

		( new PysMigrator() )->run();

		$this->assertSame( '999', $this->saved['fb_pixel_id'] );
	}

	public function test_preview_matches_what_run_imports(): void {
		$this->source( [], [ 'pys_bing' => [ 'tag_id' => '', 'uet_id' => '555' ] ] );

		$preview = ( new PysMigrator() )->preview();

		$this->assertSame( '555', $preview['bing_tag_id']['to'] );
	}
}

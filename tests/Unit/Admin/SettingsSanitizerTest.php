<?php
/**
 * Tests for the settings sanitiser (textarea vs single-line routing).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Admin\SettingsSanitizer;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Admin\SettingsSanitizer
 */
final class SettingsSanitizerTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		// Shared harness: SettingsSanitizer::sanitize() always reads the
		// current options via Options::get_all(), which calls these two.
		// No saved options -> Options falls back to its own defaults.
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'wp_parse_args' )->alias(
			static fn ( $args, $defaults = [] ): array => array_merge( (array) $defaults, (array) $args )
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_preserves_line_breaks_in_thankyou_urls(): void {
		// Real sanitize_textarea_field() keeps internal newlines, only
		// trimming the ends - unlike sanitize_text_field().
		Functions\when( 'sanitize_textarea_field' )->alias(
			static fn ( $value ): string => trim( (string) $value )
		);

		$result = SettingsSanitizer::sanitize( [ 'event_thankyou_urls' => "koszonjuk\nthank-you" ] );

		$this->assertSame( "koszonjuk\nthank-you", $result['event_thankyou_urls'] );
	}

	public function test_sanitizes_ordinary_text_option_without_textarea_routing(): void {
		// Real sanitize_text_field() collapses \r\n\t and runs of spaces
		// into a single space - the opposite of the textarea behaviour.
		Functions\when( 'sanitize_text_field' )->alias(
			static fn ( $value ): string => trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', (string) $value ) )
		);

		$result = SettingsSanitizer::sanitize( [ 'fb_pixel_id' => "123\n456" ] );

		$this->assertSame( '123 456', $result['fb_pixel_id'] );
	}

	public function test_absent_checkbox_becomes_false(): void {
		$result = SettingsSanitizer::sanitize( [] );

		$this->assertFalse( $result['fb_enabled'] );
	}

	public function test_present_truthy_checkbox_becomes_true(): void {
		$result = SettingsSanitizer::sanitize( [ 'fb_enabled' => '1' ] );

		$this->assertTrue( $result['fb_enabled'] );
	}

	/**
	 * Raw code keys (head/footer/body-open) require unfiltered_html: without
	 * it a submitted value is discarded and the previously stored value is
	 * kept, to stop privilege escalation via injected markup.
	 */
	public function test_raw_code_falls_back_to_stored_value_without_unfiltered_html_capability(): void {
		Functions\when( 'get_option' )->justReturn( [ 'head_code' => '<script>existing</script>' ] );
		Functions\when( 'current_user_can' )->justReturn( false );

		$result = SettingsSanitizer::sanitize( [ 'head_code' => '<script>injected</script>' ] );

		$this->assertSame( '<script>existing</script>', $result['head_code'] );
	}
}

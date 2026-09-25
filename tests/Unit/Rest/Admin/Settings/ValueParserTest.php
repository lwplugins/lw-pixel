<?php
/**
 * Tests for the per-type settings parser.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Rest\Admin\Settings;

use LightweightPlugins\Pixel\Rest\Admin\Settings\FieldSchema;
use LightweightPlugins\Pixel\Rest\Admin\Settings\ValueParser;
use LightweightPlugins\Pixel\Tests\Unit\Rest\Admin\RestTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Rest\Admin\Settings\ValueParser
 * @covers \LightweightPlugins\Pixel\Rest\Admin\Settings\FieldSchema
 */
final class ValueParserTest extends RestTestCase {

	/**
	 * Parse a value against the rule of an option key.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Value.
	 * @return array{0: mixed, 1: string|null}
	 */
	private function parse( string $key, mixed $value ): array {
		return ValueParser::parse( (array) FieldSchema::rule( $key ), $value, [ 'fb', 'ga4', 'chatgpt' ] );
	}

	/**
	 * @dataProvider provide_booleans
	 */
	public function test_accepts_only_strict_booleans( mixed $input, ?bool $expected ): void {
		$this->assertSame( $expected, ValueParser::bool( $input )[0] );
	}

	public static function provide_booleans(): array {
		return [
			'true'         => [ true, true ],
			'zero string'  => [ '0', false ],
			'one'          => [ 1, true ],
			'false string' => [ 'false', null ],
			'null'         => [ null, null ],
		];
	}

	public function test_meta_pixel_id_must_be_numeric(): void {
		$this->assertSame( '1234567890', $this->parse( 'fb_pixel_id', ' 1234567890 ' )[0] );
		$this->assertNotNull( $this->parse( 'fb_pixel_id', '123/../me' )[1] );
	}

	public function test_google_ids_are_upper_cased_before_the_check(): void {
		$this->assertSame( [ 'G-ABC1234', null ], $this->parse( 'ga4_measurement_id', 'g-abc1234' ) );
		$this->assertNotNull( $this->parse( 'gtm_container_id', 'AW-123' )[1] );
	}

	public function test_an_empty_id_clears_it(): void {
		$this->assertSame( [ '', null ], $this->parse( 'tiktok_pixel_id', '' ) );
	}

	public function test_undocumented_ids_only_need_a_safe_charset(): void {
		$this->assertSame( [ 'px_Ab-12', null ], $this->parse( 'chatgpt_pixel_id', 'px_Ab-12' ) );
		$this->assertNotNull( $this->parse( 'chatgpt_pixel_id', 'a b' )[1] );
	}

	public function test_scroll_thresholds_are_sorted_and_deduplicated(): void {
		$this->assertSame( [ '25,50,100', null ], $this->parse( 'event_scroll_thresholds', '100, 25,50,25' ) );
	}

	public function test_scroll_threshold_over_100_is_rejected(): void {
		$this->assertNotNull( $this->parse( 'event_scroll_thresholds', '50,150' )[1] );
	}

	public function test_download_extensions_are_lower_cased_without_dots(): void {
		$this->assertSame( [ 'pdf,zip', null ], $this->parse( 'event_download_extensions', '.PDF, zip' ) );
		$this->assertNotNull( $this->parse( 'event_download_extensions', 'pdf,tar.gz' )[1] );
	}

	public function test_thank_you_urls_drop_blank_lines(): void {
		$this->assertSame( [ "koszonjuk\nthank-you", null ], $this->parse( 'event_thankyou_urls', "koszonjuk\r\n\r\n thank-you " ) );
	}

	public function test_ldu_mode_is_a_whitelist(): void {
		$this->assertSame( [ 'force_california', null ], $this->parse( 'compliance_ldu_mode', 'force_california' ) );
		$this->assertNotNull( $this->parse( 'compliance_ldu_mode', 'texas' )[1] );
	}

	public function test_consent_lists_accept_registered_pixels_only(): void {
		$this->assertSame( [ [ 'fb', 'ga4' ], null ], $this->parse( 'consent_marketing_pixels', [ 'fb', 'ga4', 'fb' ] ) );
		$this->assertNotNull( $this->parse( 'consent_marketing_pixels', [ 'nope' ] )[1] );
	}

	public function test_custom_code_keeps_backslashes(): void {
		$this->assertSame( [ '<script>/\d+/.test(x)</script>', null ], $this->parse( 'head_code', " <script>/\\d+/.test(x)</script>\n" ) );
	}

	public function test_custom_code_over_the_size_limit_is_rejected(): void {
		$this->assertNotNull( $this->parse( 'footer_code', str_repeat( 'a', FieldSchema::MAX_CODE_BYTES + 1 ) )[1] );
	}

	public function test_retired_options_have_no_rule(): void {
		$this->assertNull( FieldSchema::rule( 'debug_mode' ) );
	}
}

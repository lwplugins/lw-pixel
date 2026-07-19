<?php
/**
 * Tests for the thank-you page event matcher.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Events;

use LightweightPlugins\Pixel\Events\ThankYou;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\Events\ThankYou
 */
final class ThankYouTest extends TestCase {

	public function test_matches_a_configured_fragment(): void {
		$this->assertSame( 'koszonjuk', ThankYou::match_pattern( '/koszonjuk-a-jelentkezest/', "koszonjuk\nthank-you" ) );
	}

	public function test_returns_empty_when_nothing_matches(): void {
		$this->assertSame( '', ThankYou::match_pattern( '/blog/hello-world/', "koszonjuk\nthank-you" ) );
	}

	public function test_matching_is_case_insensitive(): void {
		$this->assertSame( 'Thank-You', ThankYou::match_pattern( '/THANK-YOU/', 'Thank-You' ) );
	}

	public function test_blank_lines_are_ignored(): void {
		$this->assertSame( '', ThankYou::match_pattern( '/blog/', "\n   \n" ) );
	}

	public function test_empty_pattern_list_never_matches(): void {
		$this->assertSame( '', ThankYou::match_pattern( '/koszonjuk/', '' ) );
	}

	public function test_returns_the_first_matching_pattern(): void {
		$this->assertSame( 'koszonjuk', ThankYou::match_pattern( '/koszonjuk-thank-you/', "koszonjuk\nthank-you" ) );
	}

	public function test_handles_carriage_returns(): void {
		$this->assertSame( 'thank-you', ThankYou::match_pattern( '/thank-you/', "koszonjuk\r\nthank-you" ) );
	}
}

<?php
/**
 * Tests for the "Event=label" Google Ads conversion list.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Pixels;

use LightweightPlugins\Pixel\Pixels\GoogleAdsConversions;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\Pixels\GoogleAdsConversions
 */
final class GoogleAdsConversionsTest extends TestCase {

	public function test_parses_one_event_per_line(): void {
		$this->assertSame(
			[
				'Lead'         => 'AbC-12_x',
				'QuoteRequest' => 'q1',
			],
			GoogleAdsConversions::parse( "Lead = AbC-12_x\r\n\n  QuoteRequest=q1  " )
		);
	}

	public function test_skips_lines_that_are_not_event_equals_label(): void {
		$this->assertSame( [ 'Lead' => 'ok' ], GoogleAdsConversions::parse( "Lead=ok\nno equals sign\nBad Name=x\nContact=has space" ) );
	}

	public function test_reports_the_invalid_lines(): void {
		$this->assertSame( [ 'no equals sign', 'Contact=has space' ], GoogleAdsConversions::invalid_lines( "Lead=ok\nno equals sign\n\nContact=has space" ) );
	}
}

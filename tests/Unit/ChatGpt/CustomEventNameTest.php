<?php
/**
 * Tests for ChatGPT Ads custom event names.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\ChatGpt;

use LightweightPlugins\Pixel\ChatGpt\CustomEventName;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\ChatGpt\CustomEventName
 */
final class CustomEventNameTest extends TestCase {

	public function test_normalizes_free_text(): void {
		$this->assertSame( 'newsletter_signup', CustomEventName::normalize( ' Newsletter Signup! ' ) );
		$this->assertSame( 'video-play_50', CustomEventName::normalize( 'Video-Play 50%' ) );
	}

	public function test_trims_to_64_and_ends_alphanumeric(): void {
		$name = CustomEventName::normalize( str_repeat( 'a', 63 ) . '_b' );

		$this->assertSame( str_repeat( 'a', 63 ), $name );
		$this->assertLessThanOrEqual( 64, strlen( CustomEventName::normalize( str_repeat( 'x', 100 ) ) ) );
	}

	public function test_rejects_empty_and_builtin_names(): void {
		$this->assertSame( '', CustomEventName::normalize( '!!!' ) );
		$this->assertSame( '', CustomEventName::normalize( 'Order Created' ) );
		$this->assertSame( '', CustomEventName::normalize( 'custom' ) );
	}

	public function test_is_valid(): void {
		$this->assertTrue( CustomEventName::is_valid( 'a' ) );
		$this->assertFalse( CustomEventName::is_valid( '_a' ) );
		$this->assertFalse( CustomEventName::is_valid( 'a-' ) );
		$this->assertFalse( CustomEventName::is_valid( 'A' ) );
	}
}

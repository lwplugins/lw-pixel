<?php
/**
 * Tests for the Meta advanced-matching hasher.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\AdvancedMatching;

use LightweightPlugins\Pixel\AdvancedMatching\Hasher;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\AdvancedMatching\Hasher
 */
final class HasherTest extends TestCase {

	public function test_names_are_lowercased_multibyte_safe(): void {
		$this->assertSame( hash( 'sha256', 'érdiannamária' ), Hasher::hash( 'fn', " ÉRDI Anna-Mária " ) );
	}

	public function test_email_is_validated_and_lowercased(): void {
		$this->assertSame( hash( 'sha256', 'a@b.co' ), Hasher::hash( 'em', ' A@B.CO ' ) );
		$this->assertNull( Hasher::hash( 'em', 'nope' ) );
	}

	public function test_phone_drops_non_digits_and_leading_zeroes(): void {
		$this->assertSame( hash( 'sha256', '36301234567' ), Hasher::hash( 'ph', '036301234567' ) );
	}
}

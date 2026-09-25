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

	/**
	 * Meta: zp is "lowercase with no spaces and no dash".
	 */
	public function test_postcode_drops_spaces_and_dashes(): void {
		$this->assertSame( hash( 'sha256', 'sw1a1aa' ), Hasher::hash( 'zp', 'SW1A 1AA' ) );
		$this->assertSame( hash( 'sha256', '1234567' ), Hasher::hash( 'zp', '123-4567' ) );
	}

	/**
	 * Meta: "Use only the first 5 digits for U.S. zip codes".
	 */
	public function test_us_zip_plus_four_is_cut_to_five_digits(): void {
		$us = Hasher::build_user_data( [ 'zp' => '94025-1234', 'country' => 'US' ] );
		$hu = Hasher::build_user_data( [ 'zp' => '1051', 'country' => 'HU' ] );

		$this->assertSame( hash( 'sha256', '94025' ), $us['zp'] );
		$this->assertSame( hash( 'sha256', '1051' ), $hu['zp'] );
	}
}

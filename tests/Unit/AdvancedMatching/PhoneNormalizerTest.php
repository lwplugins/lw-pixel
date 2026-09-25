<?php
/**
 * Tests for phone normalisation.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\AdvancedMatching;

use LightweightPlugins\Pixel\AdvancedMatching\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\AdvancedMatching\PhoneNormalizer
 */
final class PhoneNormalizerTest extends TestCase {

	/**
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function numbers(): array {
		return [
			'international with plus'   => [ '+36 (30) 123-4567', '', '36301234567' ],
			'international with 00'     => [ '0036 30 123 4567', '', '36301234567' ],
			'national with trunk zero'  => [ '06 30 123 4567', '36', '36301234567' ],
			'national, code with plus'  => [ '(415) 555-0100', '+1', '14155550100' ],
			'national without country'  => [ '06301234567', '', '' ],
			'too short'                 => [ '+36 12', '', '' ],
			'too long'                  => [ '+1234567890123456', '', '' ],
			'dots and slashes stripped' => [ '+49.30/1234.5678', '', '493012345678' ],
			'empty'                     => [ '', '36', '' ],
		];
	}

	/**
	 * @dataProvider numbers
	 */
	public function test_normalize( string $phone, string $code, string $expected ): void {
		$this->assertSame( $expected, PhoneNormalizer::normalize( $phone, $code ) );
	}
}

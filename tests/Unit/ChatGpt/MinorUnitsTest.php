<?php
/**
 * Tests for ISO 4217 minor-unit conversion.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\ChatGpt;

use LightweightPlugins\Pixel\ChatGpt\MinorUnits;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\ChatGpt\MinorUnits
 */
final class MinorUnitsTest extends TestCase {

	/**
	 * @return array<string, array{0: float, 1: string, 2: int}>
	 */
	public static function amounts(): array {
		return [
			'USD two decimals'           => [ 129.99, 'USD', 12999 ],
			'HUF is two decimals in ISO' => [ 12990.0, 'HUF', 1299000 ],
			'JPY has none'               => [ 1500.0, 'JPY', 1500 ],
			'KWD has three'              => [ 1.234, 'KWD', 1234 ],
			'CLF has four'               => [ 1.2345, 'CLF', 12345 ],
			'float noise is rounded'     => [ 0.29, 'EUR', 29 ],
			'lowercase code'             => [ 10.0, 'usd', 1000 ],
		];
	}

	/**
	 * @dataProvider amounts
	 */
	public function test_to_minor( float $amount, string $currency, int $expected ): void {
		$this->assertSame( $expected, MinorUnits::to_minor( $amount, $currency ) );
	}

	public function test_code_validates_iso_format(): void {
		$this->assertSame( 'EUR', MinorUnits::code( ' eur ' ) );
		$this->assertSame( '', MinorUnits::code( 'EURO' ) );
		$this->assertSame( '', MinorUnits::code( [ 'EUR' ] ) );
	}
}

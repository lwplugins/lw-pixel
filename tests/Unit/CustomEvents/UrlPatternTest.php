<?php
/**
 * Tests for custom event page pattern matching.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\CustomEvents;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\CustomEvents\UrlPattern;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\CustomEvents\UrlPattern
 */
final class UrlPatternTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
	}

	/**
	 * @dataProvider provide_cases
	 */
	public function test_matches( string $pattern, string $uri, bool $expected ): void {
		$this->assertSame( $expected, UrlPattern::matches( $pattern, $uri ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: bool}>
	 */
	public static function provide_cases(): array {
		return [
			'empty matches all'          => [ '', '/anything/?x=1', true ],
			'trailing slash ignored'     => [ '/contact', '/contact/', true ],
			'query string ignored'       => [ '/contact', '/contact/?utm_source=x', true ],
			'pattern slash, no slash'    => [ '/contact/', '/contact', true ],
			'other page'                 => [ '/contact', '/contact-us/', false ],
			'wildcard'                   => [ '/products/*', '/products/hat/?ref=1', true ],
			'wildcard misses parent'     => [ '/products/*', '/shop/', false ],
			'root'                       => [ '/', '/?s=hat', true ],
			'root misses subpage'        => [ '/', '/about/', false ],
			'pattern with query'         => [ '/search?q=*', '/search?q=hat', true ],
			'pattern with query misses'  => [ '/search?q=*', '/search', false ],
			'full URL pattern'           => [ 'https://shop.test/contact/', '/contact', true ],
			'case insensitive'           => [ '/Contact', '/contact/', true ],
			'fragment ignored'           => [ '/contact', '/contact#form', true ],
		];
	}
}

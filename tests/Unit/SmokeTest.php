<?php
/**
 * Smoke test proving the unit suite runs without WordPress.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Options;

/**
 * Verifies the test infrastructure: PSR-4 autoloading of plugin classes and
 * Brain Monkey stubbing of WordPress functions.
 */
final class SmokeTest extends MonkeyTestCase {

	public function test_plugin_classes_autoload_without_wordpress(): void {
		$this->assertTrue( class_exists( Options::class ) );
	}

	public function test_brain_monkey_stubs_wordpress_functions(): void {
		Functions\when( 'get_bloginfo' )->justReturn( 'LW Pixel Test' );

		$this->assertSame( 'LW Pixel Test', get_bloginfo( 'name' ) );
	}
}

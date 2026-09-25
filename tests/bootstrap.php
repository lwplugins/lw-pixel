<?php
/**
 * PHPUnit bootstrap file.
 *
 * Unit tests run WITHOUT WordPress: only the Composer autoloader is loaded,
 * which also pulls in Brain Monkey. WordPress functions are stubbed per test
 * via Brain\Monkey — the setUp()/tearDown() lifecycle lives in
 * tests/Unit/MonkeyTestCase.php.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// WordPress time constants used in class constants.
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

// Plugin constants normally defined by lw-pixel.php.
if ( ! defined( 'LW_PIXEL_URL' ) ) {
	define( 'LW_PIXEL_URL', 'https://example.test/wp-content/plugins/lw-pixel/' );
}
if ( ! defined( 'LW_PIXEL_VERSION' ) ) {
	define( 'LW_PIXEL_VERSION', 'test' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

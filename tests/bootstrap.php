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

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

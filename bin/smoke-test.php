<?php
/**
 * Standalone smoke test for lw-pixel.
 *
 * Run from the plugin root:
 *   php bin/smoke-test.php
 *
 * Stubs the WordPress functions the plugin touches so we can exercise the
 * core wiring (Options, PixelManager, EventManager, PayloadBuilder, …)
 * without booting a full WordPress site. Catches autoloader, namespace,
 * and signature regressions in seconds.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

if ( PHP_SAPI !== 'cli' ) {
	echo "smoke-test.php must be run from the CLI.\n";
	exit( 1 );
}

require __DIR__ . '/wp-stubs.php';

define( 'LW_PIXEL_VERSION', '1.0.0-test' );
define( 'LW_PIXEL_FILE', dirname( __DIR__ ) . '/lw-pixel.php' );
define( 'LW_PIXEL_PATH', dirname( __DIR__ ) . '/' );
define( 'LW_PIXEL_URL', 'http://example.test/wp-content/plugins/lw-pixel/' );

require dirname( __DIR__ ) . '/vendor/autoload.php';

use LightweightPlugins\Pixel\AdvancedMatching\Hasher;
use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\Events\EventManager;
use LightweightPlugins\Pixel\Events\PayloadBuilder;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Pixels\PixelManager;

$assertions = 0;
$failures   = [];

/**
 * Lightweight assertion helper.
 *
 * @param bool   $condition Truthy assertion.
 * @param string $message   Description.
 */
function lw_assert( bool $condition, string $message ): void {
	global $assertions, $failures;
	$assertions++;
	if ( ! $condition ) {
		$failures[] = $message;
		echo "  ✗ {$message}\n";
		return;
	}
	echo "  ✓ {$message}\n";
}

echo "\n== Options ==\n";
$defaults = Options::get_defaults();
lw_assert( is_array( $defaults ), 'Options::get_defaults() returns array' );
lw_assert( count( $defaults ) > 50, 'Options has at least 50 default keys' );
lw_assert( false === $defaults['fb_enabled'], 'fb_enabled default is false' );
lw_assert( true === $defaults['ga4_anonymize_ip'], 'ga4_anonymize_ip default is true' );
lw_assert( true === $defaults['disable_for_admins'], 'disable_for_admins default is true' );
lw_assert( '25,50,75,100' === $defaults['event_scroll_thresholds'], 'event_scroll_thresholds default csv' );

echo "\n== Pixel manager ==\n";
$manager = new PixelManager();
lw_assert( count( $manager->all() ) === 10, 'PixelManager registers 10 default providers' );
$expected_pixels = [ 'fb', 'ga4', 'gads', 'gtm', 'tiktok', 'pinterest', 'bing', 'reddit', 'snapchat', 'x' ];
foreach ( $expected_pixels as $id ) {
	lw_assert( null !== $manager->get( $id ), "Pixel '{$id}' is registered" );
}
lw_assert( [] === $manager->get_configured(), 'No pixels are configured by default' );

echo "\n== Pixel event mapping ==\n";
$fb = $manager->get( 'fb' );
$ga = $manager->get( 'ga4' );
lw_assert( null !== $fb, 'Facebook pixel exists' );
lw_assert( $fb->map_event( 'AddToCart', [] )['name'] === 'AddToCart', 'fb maps AddToCart' );
lw_assert( $fb->map_event( 'CustomFoo', [] )['type'] === 'trackCustom', 'fb falls back to trackCustom for unknowns' );
lw_assert( $ga->map_event( 'Purchase', [] )['name'] === 'purchase', 'ga4 maps Purchase to lowercase purchase' );
lw_assert( $ga->map_event( 'AddToCart', [] )['name'] === 'add_to_cart', 'ga4 maps AddToCart to add_to_cart' );

echo "\n== Advanced Matching hasher ==\n";
lw_assert( strlen( (string) Hasher::hash( 'em', 'Foo@Example.com' ) ) === 64, 'email hash is 64 chars' );
lw_assert( null === Hasher::hash( 'em', 'not-an-email' ), 'invalid email is dropped' );
lw_assert( Hasher::hash( 'em', 'foo@example.com' ) === Hasher::hash( 'em', 'FOO@example.com' ), 'email is lowercase-normalized before hashing' );
lw_assert( null !== Hasher::hash( 'ph', '+1 (650) 555-1234' ), 'phone keeps digits only' );

echo "\n== Consent manager (no plugin active) ==\n";
$consent = new ConsentManager();
$state   = $consent->get_state();
lw_assert( true === $state['necessary'], 'necessary category always allowed' );
lw_assert( $consent->is_pixel_allowed( 'fb' ), 'fb is allowed when no consent plugin is hooked' );

echo "\n== Event manager queue ==\n";
$events = new EventManager( $manager, $consent );
$events->queue( 'PageView', [ 'page_title' => 'Test' ] );
$events->queue( 'AddToCart', [ 'value' => 99 ] );
lw_assert( count( $events->get_queue() ) === 2, 'EventManager queues 2 events' );

echo "\n== PayloadBuilder ==\n";
$payload = ( new PayloadBuilder( $manager, $consent ) )->build( $events->get_queue() );
lw_assert( isset( $payload['pixels'] ), 'payload has pixels' );
lw_assert( isset( $payload['events'] ), 'payload has events' );
lw_assert( isset( $payload['consent'] ), 'payload has consent' );
lw_assert( isset( $payload['custom_events'] ), 'payload has custom_events' );
lw_assert( isset( $payload['auto_events'] ), 'payload has auto_events' );
lw_assert( isset( $payload['auto_events']['scroll'] ), 'auto_events has scroll' );
lw_assert( isset( $payload['auto_events']['time'] ), 'auto_events has time' );
lw_assert( isset( $payload['auto_events']['download'] ), 'auto_events has download' );
lw_assert( isset( $payload['auto_events']['phone'] ), 'auto_events has phone' );
lw_assert( isset( $payload['auto_events']['email'] ), 'auto_events has email' );
lw_assert( false === $payload['auto_events']['phone']['enabled'], 'phone click tracking is off by default' );
lw_assert( isset( $payload['consentClient'] ), 'payload has consentClient flag' );
lw_assert( isset( $payload['categories'] ), 'payload has categories map' );
lw_assert( false === $payload['consentClient'], 'consentClient is false with no consent plugin' );

$json = json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP );
lw_assert( false === strpos( (string) $json, '</script>' ), 'JSON output cannot contain </script>' );

echo "\n== With a configured pixel ==\n";
$_GLOBALS_OPTIONS = [
	'fb_enabled'      => true,
	'fb_pixel_id'     => '1234567890',
	'event_pageview'  => true,
];
foreach ( $_GLOBALS_OPTIONS as $k => $v ) {
	Options::set( $k, $v );
}
$manager2 = new PixelManager();
lw_assert( count( $manager2->get_configured() ) === 1, 'fb is now configured' );
lw_assert( $manager2->get( 'fb' )->is_configured(), 'fb is_configured() returns true' );

Options::set( 'event_click_phone', true );
$payload_phone = ( new PayloadBuilder( $manager2, $consent ) )->build( [] );
lw_assert( true === $payload_phone['auto_events']['phone']['enabled'], 'phone click tracking can be enabled' );
lw_assert(
	'Contact' === ( $payload_phone['auto_events']['phone']['mapped']['fb']['name'] ?? '' ),
	'phone click maps to the standard Contact event for Meta'
);
lw_assert(
	'phone' === ( $payload_phone['auto_events']['phone']['mapped']['fb']['params']['contact_method'] ?? '' ),
	'phone click carries contact_method=phone'
);
Options::set( 'event_click_phone', false );

echo "\n== Cache-safe consent gating (issue #3) ==\n";
Options::set( 'consent_marketing_pixels', [ 'fb' ] );
add_filter(
	'lw_cookie_is_category_allowed',
	static function ( $allowed, $category ) {
		// Simulate lw-cookie denying the marketing category server-side.
		return 'marketing' === $category ? false : true;
	},
	10,
	2
);
$consent3 = new ConsentManager();
lw_assert( $consent3->has_client_gating(), 'client gating detected when the lw-cookie hook is present' );
lw_assert( ! $consent3->is_pixel_allowed( 'fb' ), 'server-side consent would deny fb (marketing)' );
$payload3 = ( new PayloadBuilder( $manager2, $consent3 ) )->build( [] );
lw_assert( true === $payload3['consentClient'], 'payload advertises client-side gating' );
lw_assert( isset( $payload3['pixels']['fb'] ), 'fb is STILL emitted (cache-safe) despite denied marketing consent' );
lw_assert( 'marketing' === ( $payload3['categories']['fb'] ?? '' ), 'fb category is exposed for client-side gating' );

echo "\n========================================\n";
echo "Total: {$assertions} assertions";
if ( $failures ) {
	printf( ", %d FAILED\n", count( $failures ) );
	foreach ( $failures as $f ) {
		echo "  - {$f}\n";
	}
	exit( 1 );
}
echo ", all passed.\n";
exit( 0 );

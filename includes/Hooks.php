<?php
/**
 * Public WordPress hooks (filters/actions) for third-party integration.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel;

use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\Pixels\PixelManager;

/**
 * Exposes filters that other plugins can use.
 *
 * Example usage:
 *
 *  // Force-disable a pixel by id (e.g. 'fb', 'ga4').
 *  add_filter( 'lw_pixel_pixel_enabled', function( $enabled, $pixel_id ) {
 *      if ( $pixel_id === 'fb' && /* some condition * / ) return false;
 *      return $enabled;
 *  }, 10, 2 );
 *
 *  // Inject custom event params before dispatch.
 *  add_filter( 'lw_pixel_event_params', function( $params, $event_name ) {
 *      $params['custom_dimension'] = 'foo';
 *      return $params;
 *  }, 10, 2 );
 */
final class Hooks {

	/**
	 * Pixel manager.
	 *
	 * @var PixelManager
	 */
	private PixelManager $pixel_manager;

	/**
	 * Consent manager.
	 *
	 * @var ConsentManager
	 */
	private ConsentManager $consent_manager;

	/**
	 * Constructor.
	 *
	 * @param PixelManager   $pixel_manager   Pixel manager instance.
	 * @param ConsentManager $consent_manager Consent manager instance.
	 */
	public function __construct( PixelManager $pixel_manager, ConsentManager $consent_manager ) {
		$this->pixel_manager   = $pixel_manager;
		$this->consent_manager = $consent_manager;

		$this->register_filters();
	}

	/**
	 * Register filter callbacks.
	 *
	 * @return void
	 */
	private function register_filters(): void {
		add_filter( 'lw_pixel_active_pixels', [ $this, 'get_active_pixels' ] );
		add_filter( 'lw_pixel_consent_state', [ $this, 'get_consent_state' ] );
	}

	/**
	 * Active pixel IDs.
	 *
	 * @param array<int, string> $_default Default value.
	 * @return array<int, string>
	 */
	public function get_active_pixels( array $_default ): array {
		unset( $_default );
		return array_keys( $this->pixel_manager->get_configured() );
	}

	/**
	 * Current consent state per category.
	 *
	 * @param array<string, bool> $_default Default value.
	 * @return array<string, bool>
	 */
	public function get_consent_state( array $_default ): array {
		unset( $_default );
		return $this->consent_manager->get_state();
	}
}

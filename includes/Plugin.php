<?php
/**
 * Main Plugin class.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel;

use LightweightPlugins\Pixel\Admin\SettingsPage;
use LightweightPlugins\Pixel\Compliance\LduMode;
use LightweightPlugins\Pixel\Compliance\MedicalMode;
use LightweightPlugins\Pixel\Consent\Manager as ConsentManager;
use LightweightPlugins\Pixel\CustomEvents\MetaBoxes as CustomEventMetaBoxes;
use LightweightPlugins\Pixel\CustomEvents\PostType as CustomEventPostType;
use LightweightPlugins\Pixel\Events\AutoEvents;
use LightweightPlugins\Pixel\Events\EventManager;
use LightweightPlugins\Pixel\Forms\FormDetector;
use LightweightPlugins\Pixel\Frontend\HeadFooterScripts;
use LightweightPlugins\Pixel\Frontend\ScriptLoader;
use LightweightPlugins\Pixel\Pixels\PixelManager;
use LightweightPlugins\Pixel\Server\OrderEnrich;
use LightweightPlugins\Pixel\SiteManager\Integration as SiteManagerIntegration;
use LightweightPlugins\Pixel\Tools\MigrationRunner;
use LightweightPlugins\Pixel\WooCommerce\Integration as WooCommerceIntegration;

/**
 * Coordinates all plugin components.
 */
final class Plugin {

	/**
	 * Pixel manager.
	 *
	 * @var PixelManager
	 */
	private PixelManager $pixel_manager;

	/**
	 * Event manager.
	 *
	 * @var EventManager
	 */
	private EventManager $event_manager;

	/**
	 * Consent manager.
	 *
	 * @var ConsentManager
	 */
	private ConsentManager $consent_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->consent_manager = new ConsentManager();
		$this->pixel_manager   = new PixelManager();
		$this->event_manager   = new EventManager( $this->pixel_manager, $this->consent_manager );

		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'init', [ $this, 'register_post_types' ] );
	}

	/**
	 * Bootstrap subsystems.
	 *
	 * @return void
	 */
	private function init_components(): void {
		SiteManagerIntegration::init();
		AutoEvents::register();
		OrderEnrich::register();
		MedicalMode::register();
		LduMode::register();
		MigrationRunner::register();

		new Hooks( $this->pixel_manager, $this->consent_manager );

		if ( is_admin() ) {
			new SettingsPage();
		}

		if ( $this->should_skip_frontend() ) {
			return;
		}

		new ScriptLoader( $this->pixel_manager, $this->event_manager );
		new HeadFooterScripts();
		new FormDetector( $this->event_manager );

		if ( is_woocommerce_active() ) {
			new WooCommerceIntegration( $this->event_manager );
		}
	}

	/**
	 * Register custom post types.
	 *
	 * @return void
	 */
	public function register_post_types(): void {
		CustomEventPostType::register();
		CustomEventMetaBoxes::register();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'lw-pixel',
			false,
			dirname( plugin_basename( LW_PIXEL_FILE ) ) . '/languages'
		);
	}

	/**
	 * Whether to skip frontend bootstrap (admins disabled, AJAX cron, REST).
	 *
	 * @return bool
	 */
	private function should_skip_frontend(): bool {
		if ( wp_doing_cron() ) {
			return true;
		}

		if ( Options::get( 'disable_for_admins' ) && current_user_can( 'manage_options' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Pixel manager accessor.
	 *
	 * @return PixelManager
	 */
	public function get_pixel_manager(): PixelManager {
		return $this->pixel_manager;
	}

	/**
	 * Event manager accessor.
	 *
	 * @return EventManager
	 */
	public function get_event_manager(): EventManager {
		return $this->event_manager;
	}

	/**
	 * Consent manager accessor.
	 *
	 * @return ConsentManager
	 */
	public function get_consent_manager(): ConsentManager {
		return $this->consent_manager;
	}
}

<?php
/**
 * Settings Page (lw-pixel).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin;

use LightweightPlugins\Pixel\Rest\Admin\Routes;
use LightweightPlugins\Pixel\Rest\Admin\Settings\SettingsMeta;

/**
 * The Pixel screen: a mount point for the React admin (build/index), which
 * reads and writes through the lw-pixel/v1 REST routes.
 */
final class SettingsPage {

	/**
	 * Settings page slug.
	 */
	public const SLUG = 'lw-pixel';

	/**
	 * Script and style handle.
	 */
	private const HANDLE = 'lw-pixel-admin-app';

	/**
	 * Hook suffix returned by add_submenu_page().
	 *
	 * Assets are keyed on it rather than on a hard-coded
	 * "lw-plugins_page_lw-pixel": WordPress derives that prefix from the
	 * translated parent menu title, so a locale that translates "LW Plugins"
	 * would silently stop the screen from loading.
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
	}

	/**
	 * Add menu page under LW Plugins.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		ParentPage::maybe_register();

		$hook = add_submenu_page(
			ParentPage::SLUG,
			__( 'Pixel', 'lw-pixel' ),
			__( 'Pixel', 'lw-pixel' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);

		$this->hook_suffix = is_string( $hook ) ? $hook : '';
	}

	/**
	 * Enqueue the React app on the settings screen.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( '' === $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}

		if ( ! BuildAssets::enqueue( 'index', self::HANDLE ) ) {
			return;
		}

		wp_add_inline_script(
			self::HANDLE,
			'window.lwPixelAdmin = ' . wp_json_encode(
				[
					'version'   => LW_PIXEL_VERSION,
					'namespace' => Routes::NAMESPACE,
					'docsUrl'   => SettingsMeta::DOCS_URL,
				]
			) . ';',
			'before'
		);
	}

	/**
	 * Mark the settings screen body for the app's styles.
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function body_class( string $classes ): string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( '' === $this->hook_suffix || ! $screen || $screen->id !== $this->hook_suffix ) {
			return $classes;
		}

		return $classes . ' lw-pixel-screen';
	}

	/**
	 * Render the mount point (or a notice when the build is missing).
	 *
	 * The mount point sits outside .wrap so core's .wrap margins and
	 * NoticeManager's direct-child notice rules never reach the app; the
	 * missing-build notice carries `lw-notice` so it is not hidden.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! BuildAssets::exists( 'index' ) ) {
			printf(
				'<div class="wrap"><h1>%s</h1><div class="notice notice-error lw-notice"><p>%s</p></div></div>',
				esc_html__( 'LW Pixel', 'lw-pixel' ),
				esc_html__( 'The settings screen files are missing. Re-install the plugin from a release ZIP, or run "npm install && npm run build" in the plugin directory.', 'lw-pixel' )
			);
			return;
		}

		echo '<div id="lw-pixel-root" class="lw-pixel-root"></div>';
	}
}

<?php
/**
 * Settings Page (lw-pixel).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin;

use LightweightPlugins\Pixel\Admin\Settings\TabInterface;
use LightweightPlugins\Pixel\Options;

/**
 * Coordinates the lw-pixel admin settings page.
 */
final class SettingsPage {

	public const SLUG = 'lw-pixel';

	private const SETTINGS_GROUP = 'lw_pixel_settings';

	/**
	 * Settings tabs.
	 *
	 * @var array<int, TabInterface>
	 */
	private array $tabs;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->tabs = TabRegistry::all();

		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Add menu page under LW Plugins.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		ParentPage::maybe_register();

		add_submenu_page(
			ParentPage::SLUG,
			__( 'Pixel', 'lw-pixel' ),
			__( 'Pixel', 'lw-pixel' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	/**
	 * Enqueue admin assets on plugin pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$valid_hooks = [
			'toplevel_page_' . ParentPage::SLUG,
			ParentPage::SLUG . '_page_' . self::SLUG,
		];

		if ( ! in_array( $hook, $valid_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'lw-pixel-admin',
			LW_PIXEL_URL . 'assets/css/admin.css',
			[],
			LW_PIXEL_VERSION
		);

		wp_enqueue_script(
			'lw-pixel-admin',
			LW_PIXEL_URL . 'assets/js/admin.js',
			[],
			LW_PIXEL_VERSION,
			true
		);
	}

	/**
	 * Register the settings group + sanitiser.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			Options::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => Options::get_defaults(),
			]
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param array<string, mixed> $input Submitted values.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		return SettingsSanitizer::sanitize( $input );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		( new SettingsRenderer( $this->tabs ) )->render_page( self::SETTINGS_GROUP );
	}
}

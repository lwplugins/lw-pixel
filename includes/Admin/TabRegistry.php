<?php
/**
 * Settings tab registry.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin;

use LightweightPlugins\Pixel\Admin\Settings\TabAdvanced;
use LightweightPlugins\Pixel\Admin\Settings\TabBing;
use LightweightPlugins\Pixel\Admin\Settings\TabSystemReport;
use LightweightPlugins\Pixel\Admin\Settings\TabCompliance;
use LightweightPlugins\Pixel\Admin\Settings\TabEvents;
use LightweightPlugins\Pixel\Admin\Settings\TabFacebook;
use LightweightPlugins\Pixel\Admin\Settings\TabGoogle;
use LightweightPlugins\Pixel\Admin\Settings\TabGTM;
use LightweightPlugins\Pixel\Admin\Settings\TabInterface;
use LightweightPlugins\Pixel\Admin\Settings\TabPinterest;
use LightweightPlugins\Pixel\Admin\Settings\TabReddit;
use LightweightPlugins\Pixel\Admin\Settings\TabSnapchat;
use LightweightPlugins\Pixel\Admin\Settings\TabTikTok;
use LightweightPlugins\Pixel\Admin\Settings\TabTools;
use LightweightPlugins\Pixel\Admin\Settings\TabWooCommerce;
use LightweightPlugins\Pixel\Admin\Settings\TabX;

/**
 * Builds the ordered list of settings tabs.
 */
final class TabRegistry {

	/**
	 * Resolve all tabs.
	 *
	 * @return array<int, TabInterface>
	 */
	public static function all(): array {
		$tabs = [
			new TabFacebook(),
			new TabGoogle(),
			new TabGTM(),
			new TabTikTok(),
			new TabPinterest(),
			new TabBing(),
			new TabReddit(),
			new TabSnapchat(),
			new TabX(),
			new TabEvents(),
			new TabWooCommerce(),
			new TabCompliance(),
			new TabAdvanced(),
			new TabTools(),
			new TabSystemReport(),
		];

		/**
		 * Filter the registered tabs.
		 *
		 * @param array<int, TabInterface> $tabs Settings tabs.
		 */
		return (array) apply_filters( 'lw_pixel_settings_tabs', $tabs );
	}
}

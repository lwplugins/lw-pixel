<?php
/**
 * Settings Tab Interface.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Contract for a settings tab.
 */
interface TabInterface {

	public function get_slug(): string;

	public function get_label(): string;

	public function get_icon(): string;

	public function render(): void;
}

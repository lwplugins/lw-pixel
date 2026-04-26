<?php
/**
 * Abstract pixel base class.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

use LightweightPlugins\Pixel\Options;

/**
 * Shared behaviour for all pixel providers.
 */
abstract class AbstractPixel implements PixelInterface {

	/**
	 * Read a plugin option scoped to this provider.
	 *
	 * @param string $key     Option key (without provider prefix).
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_option( string $key, mixed $default = null ): mixed {
		return Options::get( $this->prefix() . $key, $default );
	}

	/**
	 * Provider option prefix (e.g. "fb_").
	 *
	 * @return string
	 */
	abstract protected function prefix(): string;

	/**
	 * Default: enabled when the global toggle is on.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$enabled = (bool) $this->get_option( 'enabled' );
		return (bool) apply_filters( 'lw_pixel_pixel_enabled', $enabled, $this->get_id() );
	}

	/**
	 * Default: a pixel is configured when enabled and has a non-empty primary id.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return $this->is_enabled() && '' !== trim( (string) $this->primary_id() );
	}

	/**
	 * Primary identifier (pixel id, measurement id, container id…).
	 *
	 * @return string
	 */
	abstract protected function primary_id(): string;
}

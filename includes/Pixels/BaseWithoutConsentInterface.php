<?php
/**
 * A pixel whose base script may load before consent.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * For providers whose base script serves a purpose that needs no consent
 * (Barion: payment fraud prevention). The script loads for every visitor;
 * events still wait for the pixel's consent category.
 */
interface BaseWithoutConsentInterface {

	/**
	 * Whether the base script loads before the visitor consents.
	 *
	 * @return bool
	 */
	public function loads_base_without_consent(): bool;
}

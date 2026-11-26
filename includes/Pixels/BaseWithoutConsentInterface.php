<?php
/**
 * A pixel whose base script may load before consent.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * For providers whose base script may load without consent: Barion's
 * fraud-prevention pixel, and the Google tag under Consent Mode v2 (opt-in,
 * cookieless until consent). The script loads for every visitor; events
 * still wait for the pixel's consent category.
 */
interface BaseWithoutConsentInterface {

	/**
	 * Whether the base script loads before the visitor consents.
	 *
	 * @return bool
	 */
	public function loads_base_without_consent(): bool;
}

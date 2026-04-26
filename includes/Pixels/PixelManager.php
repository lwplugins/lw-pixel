<?php
/**
 * Pixel registry.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

/**
 * Registers and exposes the configured pixel providers.
 */
final class PixelManager {

	/**
	 * Pixel registry.
	 *
	 * @var array<string, PixelInterface>
	 */
	private array $pixels = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_defaults();
	}

	/**
	 * Register the built-in pixel providers.
	 *
	 * @return void
	 */
	private function register_defaults(): void {
		$defaults = [
			new FacebookPixel(),
			new GoogleAnalytics4(),
			new GoogleAds(),
			new GoogleTagManager(),
			new TikTokPixel(),
			new PinterestPixel(),
			new BingPixel(),
			new RedditPixel(),
			new SnapchatPixel(),
			new XPixel(),
		];

		foreach ( $defaults as $pixel ) {
			$this->register( $pixel );
		}

		do_action( 'lw_pixel_register_pixels', $this );
	}

	/**
	 * Register a pixel provider.
	 *
	 * @param PixelInterface $pixel Pixel instance.
	 * @return void
	 */
	public function register( PixelInterface $pixel ): void {
		$this->pixels[ $pixel->get_id() ] = $pixel;
	}

	/**
	 * Get a pixel by id.
	 *
	 * @param string $id Pixel id.
	 * @return PixelInterface|null
	 */
	public function get( string $id ): ?PixelInterface {
		return $this->pixels[ $id ] ?? null;
	}

	/**
	 * All registered pixels.
	 *
	 * @return array<string, PixelInterface>
	 */
	public function all(): array {
		return $this->pixels;
	}

	/**
	 * All configured pixels.
	 *
	 * @return array<string, PixelInterface>
	 */
	public function get_configured(): array {
		return array_filter(
			$this->pixels,
			static fn ( PixelInterface $pixel ): bool => $pixel->is_configured()
		);
	}

	/**
	 * Is any pixel configured?
	 *
	 * @return bool
	 */
	public function has_any_configured(): bool {
		return [] !== $this->get_configured();
	}
}

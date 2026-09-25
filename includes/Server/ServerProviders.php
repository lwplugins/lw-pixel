<?php
/**
 * Registry of server-side providers.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server;

use LightweightPlugins\Pixel\Server\Providers\ChatGptProvider;
use LightweightPlugins\Pixel\Server\Providers\Ga4Provider;
use LightweightPlugins\Pixel\Server\Providers\MetaProvider;
use LightweightPlugins\Pixel\Server\Providers\ServerProviderInterface;

/**
 * Meta Conversions API, GA4 Measurement Protocol and ChatGPT Ads
 * Conversions API, plus any provider added with the
 * `lw_pixel_server_providers` filter.
 */
final class ServerProviders {

	/**
	 * Every registered provider, keyed by id.
	 *
	 * @return array<string, ServerProviderInterface>
	 */
	public static function all(): array {
		$providers = [];

		/**
		 * Filter the server-side providers.
		 *
		 * @param array<int, mixed> $list Providers (ServerProviderInterface instances).
		 */
		$list = (array) apply_filters( 'lw_pixel_server_providers', [ new MetaProvider(), new Ga4Provider(), new ChatGptProvider() ] );

		foreach ( $list as $provider ) {
			if ( $provider instanceof ServerProviderInterface ) {
				$providers[ $provider->id() ] = $provider;
			}
		}

		return $providers;
	}

	/**
	 * Enabled and configured providers.
	 *
	 * @return array<string, ServerProviderInterface>
	 */
	public static function active(): array {
		return array_filter(
			self::all(),
			static fn ( ServerProviderInterface $provider ): bool => $provider->is_active()
		);
	}

	/**
	 * A provider by id.
	 *
	 * @param string $id Provider id.
	 * @return ServerProviderInterface|null
	 */
	public static function get( string $id ): ?ServerProviderInterface {
		return self::all()[ $id ] ?? null;
	}
}

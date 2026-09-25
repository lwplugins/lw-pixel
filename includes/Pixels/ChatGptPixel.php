<?php
/**
 * ChatGPT Ads measurement pixel provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

use LightweightPlugins\Pixel\ChatGpt\EventMapper;
use LightweightPlugins\Pixel\ChatGpt\UserData;
use LightweightPlugins\Pixel\Server\CustomerData;

/**
 * Maps generic events to `oaiq("measure", type, data, options)` calls.
 * The Conversions API uses the same EventMapper, so both copies match.
 */
final class ChatGptPixel extends AbstractPixel {

	public function get_id(): string {
		return 'chatgpt';
	}

	public function get_label(): string {
		return __( 'ChatGPT Ads', 'lw-pixel' );
	}

	protected function prefix(): string {
		return 'chatgpt_';
	}

	protected function primary_id(): string {
		return (string) $this->get_option( 'pixel_id', '' );
	}

	public function get_frontend_config(): array {
		$config = [
			'pixelId' => $this->primary_id(),
			'debug'   => (bool) $this->get_option( 'debug' ),
		];

		$user = $this->browser_user();
		if ( [] !== $user ) {
			$config['user'] = $user;
		}

		return $config;
	}

	public function map_event( string $event_name, array $params ): ?array {
		$mapped = EventMapper::map( $event_name, $params );

		if ( null === $mapped ) {
			return null;
		}

		$payload = [
			'name' => $mapped['type'],
			'data' => $mapped['data'],
		];

		if ( isset( $mapped['custom_event_name'] ) ) {
			$payload['options'] = [ 'custom_event_name' => $mapped['custom_event_name'] ];
		}

		return $payload;
	}

	/**
	 * Hashed advanced-matching data of the logged-in user, for `init`.
	 *
	 * Per-user data makes the page per-user: it is flagged as not cacheable.
	 *
	 * @return array<string, string>
	 */
	private function browser_user(): array {
		if ( ! $this->get_option( 'advanced_matching' ) || ! is_user_logged_in() ) {
			return [];
		}

		/**
		 * Filter the hashed advanced-matching data given to the pixel's init.
		 *
		 * @param array<string, string> $user Hashed / plain matching fields.
		 */
		$user = (array) apply_filters( 'lw_pixel_chatgpt_browser_user', UserData::for_browser( CustomerData::current_user() ) );

		if ( [] !== $user && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- shared page-cache convention constant.
		}

		return $user;
	}
}

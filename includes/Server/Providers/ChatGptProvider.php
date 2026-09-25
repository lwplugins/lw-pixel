<?php
/**
 * ChatGPT Ads Conversions API server provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server\Providers;

use LightweightPlugins\Pixel\ChatGpt\EventMapper;
use LightweightPlugins\Pixel\ChatGpt\UserData;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Server\ChatGptCAPI;

/**
 * Builds Conversions API events with the same type, data and id as the
 * browser pixel's `measure` call, so OpenAI keeps only one of them.
 */
final class ChatGptProvider implements ServerProviderInterface {

	public function id(): string {
		return 'chatgpt';
	}

	public function is_active(): bool {
		return (bool) Options::get( 'chatgpt_capi_enabled' ) && ChatGptCAPI::is_configured();
	}

	public function build( string $name, array $params, string $event_id, array $context ): ?array {
		$mapped = EventMapper::map( $name, $params );
		$url    = (string) ( $context['url'] ?? '' );

		// source_url is required for web events and needs a scheme + host.
		if ( null === $mapped || 1 !== preg_match( '#^https?://[^/]+#i', $url ) ) {
			return null;
		}

		$event = [
			'id'            => $event_id,
			'type'          => $mapped['type'],
			'timestamp_ms'  => (int) ( $context['time'] ?? time() ) * 1000,
			'action_source' => 'web',
			'source_url'    => $url,
			'data'          => $mapped['data'],
		];

		if ( isset( $mapped['custom_event_name'] ) ) {
			$event['custom_event_name'] = $mapped['custom_event_name'];
		}

		if ( '' !== (string) ( $context['oppref'] ?? '' ) ) {
			$event['oppref'] = (string) $context['oppref'];
		}

		$user = self::user( $context );
		if ( [] !== $user ) {
			$event['user'] = $user;
		}

		/**
		 * Filter a ChatGPT Ads Conversions API event before it is queued.
		 *
		 * @param array  $event Event body.
		 * @param string $name  Generic LW Pixel event name.
		 */
		return (array) apply_filters( 'lw_pixel_chatgpt_capi_event', $event, $name );
	}

	public function send( array $events ): array {
		$ok     = true;
		$status = 0;

		foreach ( array_chunk( $events, ChatGptCAPI::MAX_BATCH ) as $chunk ) {
			$result = ChatGptCAPI::send( $chunk );
			$ok     = $ok && $result['ok'];
			$status = $result['status'];
		}

		return [
			'ok'     => $ok,
			'status' => $status,
		];
	}

	/**
	 * The event's `user` object: IP, user agent and the pixel's `__obref`
	 * browser reference, plus hashed customer fields with advanced matching.
	 *
	 * @param array<string, mixed> $context RequestContext shape.
	 * @return array<string, mixed>
	 */
	public static function user( array $context ): array {
		$ip   = (string) ( $context['ip'] ?? '' );
		$user = array_filter(
			[
				'ip_address' => false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '',
				'user_agent' => (string) ( $context['ua'] ?? '' ),
				'obref'      => (string) ( $context['obref'] ?? '' ),
			]
		);

		if ( Options::get( 'chatgpt_advanced_matching' ) ) {
			$user = array_merge( $user, UserData::for_capi( (array) ( $context['customer'] ?? [] ) ) );
		}

		return $user;
	}
}

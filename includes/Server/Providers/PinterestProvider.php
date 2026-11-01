<?php
/**
 * Pinterest Conversions API server provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server\Providers;

use LightweightPlugins\Pixel\AdvancedMatching\Hasher;
use LightweightPlugins\Pixel\AdvancedMatching\PhoneNormalizer;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Server\PinterestCAPI;

/**
 * Sends the Pinterest Tag's events from the server with the same event id,
 * so Pinterest keeps only one of them. InitiateCheckout stays browser-only:
 * Pinterest's `checkout` event is the completed purchase.
 */
final class PinterestProvider implements ServerProviderInterface {

	/**
	 * Generic event → Pinterest Conversions API event name.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'             => 'page_visit',
		'ViewContent'          => 'page_visit',
		'ViewCategory'         => 'view_category',
		'Search'               => 'search',
		'Lead'                 => 'lead',
		'Contact'              => 'lead',
		'AddToCart'            => 'add_to_cart',
		'Purchase'             => 'checkout',
		'CompleteRegistration' => 'signup',
		'Signup'               => 'signup',
	];

	public function id(): string {
		return 'pinterest';
	}

	public function is_active(): bool {
		return (bool) Options::get( 'pinterest_capi_enabled' ) && PinterestCAPI::is_configured();
	}

	public function build( string $name, array $params, string $event_id, array $context ): ?array {
		$event_name = self::EVENT_MAP[ $name ] ?? null;

		if ( null === $event_name ) {
			return null;
		}

		$event = [
			'event_name'       => $event_name,
			'action_source'    => 'web',
			'event_time'       => (int) ( $context['time'] ?? time() ),
			'event_id'         => $event_id,
			'event_source_url' => (string) ( $context['url'] ?? '' ),
			'user_data'        => self::user_data( $context ),
			'custom_data'      => PinterestCustomData::build( $name, $params ),
		];

		if ( '' === $event['event_source_url'] ) {
			unset( $event['event_source_url'] );
		}

		if ( [] === $event['custom_data'] ) {
			unset( $event['custom_data'] );
		}

		/**
		 * Filter a Pinterest Conversions API event before it is queued.
		 *
		 * @param array  $event Event body.
		 * @param string $name  Generic LW Pixel event name.
		 */
		$event = (array) apply_filters( 'lw_pixel_pinterest_capi_event', $event, $name );

		// Pinterest rejects an event that has neither an email hash nor
		// IP + user agent (e.g. medical mode stripped them): not sent.
		return self::is_matchable( (array) ( $event['user_data'] ?? [] ) ) ? $event : null;
	}

	public function send( array $events ): array {
		$ok     = true;
		$status = 0;

		foreach ( array_chunk( $events, PinterestCAPI::MAX_BATCH ) as $chunk ) {
			$result = PinterestCAPI::send( $chunk );
			$ok     = $ok && $result['ok'];
			$status = $result['status'];
		}

		return [
			'ok'     => $ok,
			'status' => $status,
		];
	}

	/**
	 * Pinterest user_data: IP, user agent and the `_epik` click id, plus
	 * hashed customer fields (one-element arrays) with advanced matching.
	 *
	 * @param array<string, mixed> $context RequestContext shape.
	 * @return array<string, mixed>
	 */
	public static function user_data( array $context ): array {
		$ip   = (string) ( $context['ip'] ?? '' );
		$data = array_filter(
			[
				'client_ip_address' => false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '',
				'client_user_agent' => (string) ( $context['ua'] ?? '' ),
				'click_id'          => (string) ( $context['epik'] ?? '' ),
			]
		);

		if ( ! Options::get( 'pinterest_advanced_matching' ) ) {
			return $data;
		}

		foreach ( self::hashed( (array) ( $context['customer'] ?? [] ) ) as $key => $hash ) {
			$data[ $key ] = [ $hash ];
		}

		return $data;
	}

	/**
	 * Customer fields normalised per Pinterest's spec, then SHA-256.
	 *
	 * @param array<string, mixed> $customer Raw customer data (CustomerData shape).
	 * @return array<string, string>
	 */
	private static function hashed( array $customer ): array {
		$get = static fn ( string $key ): string => trim( (string) ( $customer[ $key ] ?? '' ) );

		return Hasher::build_user_data(
			[
				'em'          => $get( 'email' ),
				'ph'          => PhoneNormalizer::normalize( $get( 'phone' ), $get( 'calling_code' ) ),
				'fn'          => $get( 'first_name' ),
				'ln'          => $get( 'last_name' ),
				// City: lowercase, without spaces or punctuation.
				'ct'          => (string) preg_replace( '/[\s!-\/:-@\[-`{-~]+/u', '', $get( 'city' ) ),
				'st'          => $get( 'region' ),
				// Zip code: digits only.
				'zp'          => (string) preg_replace( '/\D+/', '', $get( 'postal_code' ) ),
				'country'     => $get( 'country' ),
				'external_id' => $get( 'external_id' ),
			]
		);
	}

	/**
	 * Whether user_data meets Pinterest's minimum: `em`, `hashed_maids`, or
	 * both IP address and user agent.
	 *
	 * @param array<string, mixed> $user_data User data.
	 * @return bool
	 */
	private static function is_matchable( array $user_data ): bool {
		return ! empty( $user_data['em'] ) || ! empty( $user_data['hashed_maids'] )
			|| ( ! empty( $user_data['client_ip_address'] ) && ! empty( $user_data['client_user_agent'] ) );
	}
}

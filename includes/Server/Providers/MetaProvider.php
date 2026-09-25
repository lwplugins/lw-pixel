<?php
/**
 * Meta Conversions API server provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server\Providers;

use LightweightPlugins\Pixel\AdvancedMatching\Hasher;
use LightweightPlugins\Pixel\AdvancedMatching\PhoneNormalizer;
use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Server\FacebookCAPI;

/**
 * Sends Meta's standard events. Custom and non-standard events stay
 * browser-only.
 */
final class MetaProvider implements ServerProviderInterface {

	/**
	 * LW Pixel events that are Meta standard events.
	 */
	private const STANDARD = [
		'PageView',
		'ViewContent',
		'Search',
		'Lead',
		'Contact',
		'AddToCart',
		'InitiateCheckout',
		'AddPaymentInfo',
		'Purchase',
		'CompleteRegistration',
	];

	public function id(): string {
		return 'fb';
	}

	public function is_active(): bool {
		return (bool) Options::get( 'fb_capi_enabled' )
			&& '' !== trim( (string) Options::get( 'fb_pixel_id', '' ) )
			&& '' !== (string) Options::get( 'fb_capi_token', '' );
	}

	public function build( string $name, array $params, string $event_id, array $context ): ?array {
		if ( ! in_array( $name, self::STANDARD, true ) ) {
			return null;
		}

		$event = [
			'event_name'       => $name,
			'event_time'       => (int) ( $context['time'] ?? time() ),
			'event_id'         => $event_id,
			'action_source'    => 'website',
			'event_source_url' => (string) ( $context['url'] ?? '' ),
			'user_data'        => (array) apply_filters( 'lw_pixel_capi_user_data', self::user_data( $context ) ),
			'custom_data'      => MetaCustomData::build( $name, $params ),
		];

		if ( [] === $event['custom_data'] ) {
			unset( $event['custom_data'] );
		}

		return (array) apply_filters( 'lw_pixel_capi_event_body', $event, $name );
	}

	public function send( array $events ): array {
		$result = FacebookCAPI::send_events( $events );

		return [
			'ok'     => $result['ok'],
			'status' => $result['status'],
		];
	}

	/**
	 * Meta user_data: request identifiers plus, with advanced matching on,
	 * hashed customer fields (external_id only with its own option).
	 *
	 * @param array<string, mixed> $context RequestContext shape.
	 * @return array<string, string>
	 */
	public static function user_data( array $context ): array {
		$data = [
			'client_ip_address' => (string) ( $context['ip'] ?? '' ),
			'client_user_agent' => (string) ( $context['ua'] ?? '' ),
			'fbp'               => (string) ( $context['fbp'] ?? '' ),
			'fbc'               => (string) ( $context['fbc'] ?? '' ),
		];

		$customer = (array) ( $context['customer'] ?? [] );
		$raw      = [];

		if ( Options::get( 'fb_advanced_matching' ) ) {
			$raw = [
				'em'      => (string) ( $customer['email'] ?? '' ),
				'ph'      => PhoneNormalizer::normalize( (string) ( $customer['phone'] ?? '' ), (string) ( $customer['calling_code'] ?? '' ) ),
				'fn'      => (string) ( $customer['first_name'] ?? '' ),
				'ln'      => (string) ( $customer['last_name'] ?? '' ),
				'ct'      => (string) ( $customer['city'] ?? '' ),
				'st'      => (string) ( $customer['region'] ?? '' ),
				'zp'      => (string) ( $customer['postal_code'] ?? '' ),
				'country' => (string) ( $customer['country'] ?? '' ),
			];
		}

		if ( Options::get( 'fb_send_external_id' ) ) {
			$raw['external_id'] = (string) ( $customer['external_id'] ?? '' );
		}

		return array_filter( array_merge( $data, Hasher::build_user_data( $raw ) ) );
	}
}

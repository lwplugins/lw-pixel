<?php
/**
 * GA4 Measurement Protocol server provider.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server\Providers;

use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Server\GoogleAnalyticsMP;

/**
 * GA4 does not deduplicate Measurement Protocol hits against gtag.js hits
 * (only purchases, by transaction_id). So when LW Pixel's own GA4 tag runs
 * in the browser, only the purchase is also sent from the server; every
 * other event is sent from the server only when GA4 is loaded some other
 * way (e.g. through Tag Manager). Always needs the visitor's `_ga` client
 * id — without it (no GA4 tag, no analytics consent) nothing is sent.
 */
final class Ga4Provider implements ServerProviderInterface {

	/**
	 * Generic event → GA4 event name.
	 *
	 * @var array<string, string>
	 */
	private const EVENT_MAP = [
		'PageView'             => 'page_view',
		'ViewContent'          => 'view_item',
		'ViewCategory'         => 'view_item_list',
		'ViewCart'             => 'view_cart',
		'Search'               => 'search',
		'Lead'                 => 'generate_lead',
		'AddToCart'            => 'add_to_cart',
		'InitiateCheckout'     => 'begin_checkout',
		'AddPaymentInfo'       => 'add_payment_info',
		'Purchase'             => 'purchase',
		'CompleteRegistration' => 'sign_up',
		'Login'                => 'login',
	];

	public function id(): string {
		return 'ga4';
	}

	public function is_active(): bool {
		return (bool) Options::get( 'ga4_mp_enabled' )
			&& '' !== trim( (string) Options::get( 'ga4_measurement_id', '' ) )
			&& '' !== (string) Options::get( 'ga4_mp_api_secret', '' );
	}

	public function build( string $name, array $params, string $event_id, array $context ): ?array {
		$ga4_name  = self::EVENT_MAP[ $name ] ?? null;
		$client_id = (string) ( $context['ga'] ?? '' );

		if ( null === $ga4_name || '' === $client_id ) {
			return null;
		}

		if ( 'Purchase' !== $name && self::browser_tag_active() ) {
			return null;
		}

		return [
			'client_id' => $client_id,
			'event'     => [
				'name'   => $ga4_name,
				'params' => self::params( $name, $params, $event_id, $context ),
			],
		];
	}

	public function send( array $events ): array {
		$by_client = [];

		foreach ( $events as $event ) {
			$by_client[ (string) $event['client_id'] ][] = $event['event'];
		}

		$ok     = true;
		$status = 0;

		foreach ( $by_client as $client_id => $client_events ) {
			foreach ( array_chunk( $client_events, 25 ) as $chunk ) {
				$result = GoogleAnalyticsMP::send_events( (string) $client_id, $chunk );
				$ok     = $ok && $result['ok'];
				$status = $result['status'];
			}
		}

		return [
			'ok'     => $ok,
			'status' => $status,
		];
	}

	/**
	 * Whether LW Pixel's own GA4 tag is configured for the browser.
	 *
	 * @return bool
	 */
	private static function browser_tag_active(): bool {
		return (bool) Options::get( 'ga4_enabled' ) && '' !== trim( (string) Options::get( 'ga4_measurement_id', '' ) );
	}

	/**
	 * GA4 event params (ecommerce items, value, transaction id, event_id).
	 *
	 * @param string               $name     Generic event name.
	 * @param array<string, mixed> $params   Generic params.
	 * @param string               $event_id Shared event id.
	 * @param array<string, mixed> $context  RequestContext shape.
	 * @return array<string, mixed>
	 */
	private static function params( string $name, array $params, string $event_id, array $context ): array {
		$out = [
			'event_id'             => $event_id,
			'engagement_time_msec' => 1,
			'page_location'        => (string) ( $context['url'] ?? '' ),
		];

		$items = isset( $params['contents'] ) && is_array( $params['contents'] )
			? $params['contents']
			: ( isset( $params['content_id'] ) ? [ $params ] : [] );

		foreach ( $items as $item ) {
			if ( is_array( $item ) ) {
				$out['items'][] = [
					'item_id'   => (string) ( $item['content_id'] ?? '' ),
					'item_name' => (string) ( $item['content_name'] ?? '' ),
					'quantity'  => max( 1, (int) ( $item['quantity'] ?? 1 ) ),
					'price'     => (float) ( $item['price'] ?? 0 ),
				];
			}
		}

		if ( isset( $params['value'] ) && is_numeric( $params['value'] ) ) {
			$out['value'] = (float) $params['value'];
		} elseif ( isset( $params['price'] ) && is_numeric( $params['price'] ) ) {
			$out['value'] = (float) $params['price'] * max( 1, (int) ( $params['quantity'] ?? 1 ) );
		}

		if ( isset( $out['value'] ) && '' !== (string) ( $params['currency'] ?? '' ) ) {
			$out['currency'] = (string) $params['currency'];
		}

		if ( 'Purchase' === $name ) {
			$out['transaction_id'] = (string) ( $params['order_id'] ?? '' );
		}

		if ( 'Search' === $name ) {
			$out['search_term'] = (string) ( $params['search_string'] ?? '' );
		}

		return array_filter( $out, static fn ( $value ): bool => '' !== $value );
	}
}

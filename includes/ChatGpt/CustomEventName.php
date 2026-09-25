<?php
/**
 * ChatGPT Ads custom event name rules.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\ChatGpt;

/**
 * Turns a user-defined event name into a valid `custom_event_name`.
 *
 * Rules (OpenAI docs, 2026-09-26): 1–64 characters, letters, digits,
 * underscores or hyphens, starting and ending with a letter or digit; the
 * pixel docs also ask for lowercase and forbid built-in event names.
 */
final class CustomEventName {

	/**
	 * Built-in ChatGPT Ads event names (not allowed as custom names).
	 */
	public const BUILTIN = [
		'app_installed',
		'app_opened',
		'appointment_scheduled',
		'checkout_started',
		'contents_viewed',
		'custom',
		'items_added',
		'lead_created',
		'order_created',
		'page_viewed',
		'registration_completed',
		'subscription_created',
		'trial_started',
	];

	/**
	 * Normalise a name, or return '' when nothing valid remains.
	 *
	 * "Newsletter Signup!" becomes "newsletter_signup".
	 *
	 * @param string $name Raw name.
	 * @return string
	 */
	public static function normalize( string $name ): string {
		$name = strtolower( trim( $name ) );
		$name = (string) preg_replace( '/[^a-z0-9_-]+/', '_', $name );
		$name = trim( $name, '_-' );
		$name = trim( substr( $name, 0, 64 ), '_-' );

		return self::is_valid( $name ) ? $name : '';
	}

	/**
	 * Whether a name is already valid as-is.
	 *
	 * @param string $name Name.
	 * @return bool
	 */
	public static function is_valid( string $name ): bool {
		return 1 === preg_match( '/^[a-z0-9](?:[a-z0-9_-]{0,62}[a-z0-9])?$/', $name )
			&& ! in_array( $name, self::BUILTIN, true );
	}
}

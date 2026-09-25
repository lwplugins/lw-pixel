<?php
/**
 * Server-side provider contract.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Server\Providers;

interface ServerProviderInterface {

	/**
	 * Provider id — the same as its browser pixel id, which also selects
	 * its consent category ('fb', 'ga4', 'chatgpt').
	 */
	public function id(): string;

	/** Enabled and configured (credentials present). */
	public function is_active(): bool;

	/**
	 * Build the provider's event body for a generic event, or null when the
	 * provider does not send this event.
	 *
	 * @param string               $name     Generic event name.
	 * @param array<string, mixed> $params   Generic params.
	 * @param string               $event_id Event id shared with the browser copy.
	 * @param array<string, mixed> $context  RequestContext shape.
	 * @return array<string, mixed>|null
	 */
	public function build( string $name, array $params, string $event_id, array $context ): ?array;

	/**
	 * Send built events.
	 *
	 * @param array<int, array<string, mixed>> $events Built events.
	 * @return array{ok: bool, status: int}
	 */
	public function send( array $events ): array;
}

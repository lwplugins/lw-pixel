<?php
/**
 * Pixel provider interface.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Pixels;

interface PixelInterface {

	public function get_id(): string;

	public function get_label(): string;

	public function is_enabled(): bool;

	public function is_configured(): bool;

	/** @return array<string, mixed> */
	public function get_frontend_config(): array;

	/**
	 * Map a generic event to a provider-specific payload.
	 *
	 * @param string               $event_name Event name.
	 * @param array<string, mixed> $params     Event parameters.
	 * @return array<string, mixed>|null
	 */
	public function map_event( string $event_name, array $params ): ?array;
}

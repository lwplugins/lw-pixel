<?php
/**
 * Event interface.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

interface EventInterface {

	public function get_name(): string;

	public function should_fire(): bool;

	/** @return array<string, mixed> */
	public function get_params(): array;
}

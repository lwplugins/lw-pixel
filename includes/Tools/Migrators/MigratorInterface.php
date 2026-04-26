<?php
/**
 * Migrator interface.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tools\Migrators;

interface MigratorInterface {

	public function get_id(): string;

	public function get_label(): string;

	public function is_available(): bool;

	/** @return array<string, array{from: mixed, to: mixed}> */
	public function preview(): array;

	/** @return array{updated: array<int, string>, skipped: array<int, string>} */
	public function run(): array;
}

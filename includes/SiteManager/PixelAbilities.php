<?php
/**
 * Pixel ability definitions for LW Site Manager.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\SiteManager;

/**
 * Registers Pixel-specific abilities with the Abilities API.
 */
final class PixelAbilities {

	/**
	 * Register all pixel abilities.
	 *
	 * @param object $permissions Permission manager.
	 * @return void
	 */
	public static function register( object $permissions ): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		self::register_options( $permissions );
		self::register_introspection( $permissions );
	}

	/**
	 * Register get/set options abilities.
	 *
	 * @param object $permissions Permission manager.
	 * @return void
	 */
	private static function register_options( object $permissions ): void {
		wp_register_ability(
			'lw-pixel/get-options',
			[
				'label'               => __( 'Get Pixel Options', 'lw-pixel' ),
				'description'         => __( 'Get LW Pixel settings (pixel ids, event toggles, advanced).', 'lw-pixel' ),
				'category'            => 'pixel',
				'execute_callback'    => [ PixelService::class, 'get_options' ],
				'permission_callback' => $permissions->callback( 'can_manage_options' ),
				'input_schema'        => [
					'type'    => 'object',
					'default' => [],
				],
				'output_schema'       => self::object_schema( [ 'options' => [ 'type' => 'object' ] ] ),
				'meta'                => self::readonly_meta(),
			]
		);

		wp_register_ability(
			'lw-pixel/set-options',
			[
				'label'               => __( 'Set Pixel Options', 'lw-pixel' ),
				'description'         => __( 'Update LW Pixel settings.', 'lw-pixel' ),
				'category'            => 'pixel',
				'execute_callback'    => [ PixelService::class, 'set_options' ],
				'permission_callback' => $permissions->callback( 'can_manage_options' ),
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'options' ],
					'properties' => [
						'options' => [
							'type'        => 'object',
							'description' => __( 'Key-value pairs of settings to update.', 'lw-pixel' ),
						],
					],
				],
				'output_schema'       => self::object_schema(
					[
						'message' => [ 'type' => 'string' ],
						'updated' => [ 'type' => 'array' ],
					]
				),
				'meta'                => self::write_meta(),
			]
		);
	}

	/**
	 * Register introspection abilities.
	 *
	 * @param object $permissions Permission manager.
	 * @return void
	 */
	private static function register_introspection( object $permissions ): void {
		wp_register_ability(
			'lw-pixel/list-pixels',
			[
				'label'               => __( 'List Pixels', 'lw-pixel' ),
				'description'         => __( 'List all pixel providers with their configuration status.', 'lw-pixel' ),
				'category'            => 'pixel',
				'execute_callback'    => [ PixelService::class, 'list_pixels' ],
				'permission_callback' => $permissions->callback( 'can_manage_options' ),
				'input_schema'        => [
					'type'    => 'object',
					'default' => [],
				],
				'output_schema'       => self::object_schema( [ 'pixels' => [ 'type' => 'object' ] ] ),
				'meta'                => self::readonly_meta(),
			]
		);
	}

	/**
	 * Build a standard output schema with success + extras.
	 *
	 * @param array<string, mixed> $extras Extra properties.
	 * @return array<string, mixed>
	 */
	private static function object_schema( array $extras ): array {
		return [
			'type'       => 'object',
			'properties' => array_merge( [ 'success' => [ 'type' => 'boolean' ] ], $extras ),
		];
	}

	/**
	 * Read-only ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function readonly_meta(): array {
		return [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
	}

	/**
	 * Write ability metadata.
	 *
	 * @return array<string, mixed>
	 */
	private static function write_meta(): array {
		return [
			'show_in_rest' => true,
			'annotations'  => [
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => true,
			],
		];
	}
}

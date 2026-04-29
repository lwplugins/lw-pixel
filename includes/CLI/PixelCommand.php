<?php
/**
 * Pixel main CLI command.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\CLI;

use LightweightPlugins\Pixel\Options;
use WP_CLI;
use WP_CLI\Utils;

use function LightweightPlugins\Pixel\lw_pixel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LW Pixel — lightweight tracking pixel manager.
 */
final class PixelCommand {

	/**
	 * Show plugin status overview.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel status
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @subcommand status
	 */
	public function status( array $args, array $assoc_args ): void {
		unset( $args, $assoc_args );

		$options       = Options::get_all();
		$pixel_manager = lw_pixel()->get_pixel_manager();
		$configured    = count( $pixel_manager->get_configured() );
		$total         = count( $pixel_manager->all() );

		$items = [
			[
				'setting' => 'Version',
				'value'   => defined( 'LW_PIXEL_VERSION' ) ? LW_PIXEL_VERSION : '—',
			],
			[
				'setting' => 'Configured pixels',
				'value'   => $configured . ' / ' . $total,
			],
			[
				'setting' => 'Disable for admins',
				'value'   => ! empty( $options['disable_for_admins'] ) ? 'On' : 'Off',
			],
			[
				'setting' => 'Debug mode',
				'value'   => ! empty( $options['debug_mode'] ) ? 'On' : 'Off',
			],
			[
				'setting' => 'Consent mode',
				'value'   => (string) ( $options['consent_mode'] ?? '—' ),
			],
			[
				'setting' => 'Medical mode',
				'value'   => ! empty( $options['compliance_medical'] ) ? 'On' : 'Off',
			],
			[
				'setting' => 'LDU mode',
				'value'   => ! empty( $options['compliance_ldu'] )
					? 'On (' . (string) ( $options['compliance_ldu_mode'] ?? 'auto' ) . ')'
					: 'Off',
			],
			[
				'setting' => 'Meta CAPI',
				'value'   => ! empty( $options['fb_capi_enabled'] ) && ! empty( $options['fb_capi_token'] ) ? 'On' : 'Off',
			],
			[
				'setting' => 'GA4 Measurement Protocol',
				'value'   => ! empty( $options['ga4_mp_enabled'] ) && ! empty( $options['ga4_mp_api_secret'] ) ? 'On' : 'Off',
			],
		];

		Utils\format_items( 'table', $items, [ 'setting', 'value' ] );
	}

	/**
	 * List all pixel providers and their state.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * [--configured]
	 * : Only list pixels that have an ID set.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp lw-pixel list
	 *     $ wp lw-pixel list --configured
	 *     $ wp lw-pixel list --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 *
	 * @subcommand list
	 */
	public function list_pixels( array $args, array $assoc_args ): void {
		unset( $args );

		$format          = (string) Utils\get_flag_value( $assoc_args, 'format', 'table' );
		$only_configured = (bool) Utils\get_flag_value( $assoc_args, 'configured', false );

		$pixel_manager = lw_pixel()->get_pixel_manager();
		$pixels        = $only_configured ? $pixel_manager->get_configured() : $pixel_manager->all();

		if ( [] === $pixels ) {
			WP_CLI::warning( 'No pixels found.' );
			return;
		}

		$items = [];
		foreach ( $pixels as $pixel ) {
			$items[] = [
				'id'         => $pixel->get_id(),
				'label'      => $pixel->get_label(),
				'enabled'    => $pixel->is_enabled() ? 'yes' : 'no',
				'configured' => $pixel->is_configured() ? 'yes' : 'no',
			];
		}

		Utils\format_items( $format, $items, [ 'id', 'label', 'enabled', 'configured' ] );
	}
}

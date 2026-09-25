<?php
/**
 * System report REST controller.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin;

use LightweightPlugins\Pixel\Admin\SystemReport;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET lw-pixel/v1/admin/system-report: the support report (secrets and
 * custom code reported only by length).
 */
final class SystemReportController {

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		Routes::add( '/admin/system-report', [ WP_REST_Server::READABLE => 'get_report' ], $this );
	}

	/**
	 * The report.
	 *
	 * @return WP_REST_Response
	 */
	public function get_report(): WP_REST_Response {
		return new WP_REST_Response( [ 'report' => SystemReport::generate() ] );
	}
}

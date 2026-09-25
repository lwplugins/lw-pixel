<?php
/**
 * Settings import REST controller.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin;

use LightweightPlugins\Pixel\Options;
use LightweightPlugins\Pixel\Tools\MigratorRegistry;
use LightweightPlugins\Pixel\Tools\Migrators\MigratorInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET lw-pixel/v1/admin/migrators (with previews) and
 * POST lw-pixel/v1/admin/migrators/{id}/run.
 */
final class MigratorController {

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		Routes::add( '/admin/migrators', [ WP_REST_Server::READABLE => 'list_migrators' ], $this );
		Routes::add( '/admin/migrators/(?P<id>[a-z0-9_-]+)/run', [ WP_REST_Server::CREATABLE => 'run_migrator' ], $this );
	}

	/**
	 * Every registered importer with what it would change.
	 *
	 * @return WP_REST_Response
	 */
	public function list_migrators(): WP_REST_Response {
		$items = [];

		foreach ( MigratorRegistry::all() as $id => $migrator ) {
			$items[] = self::present( (string) $id, $migrator );
		}

		return new WP_REST_Response( [ 'migrators' => $items ] );
	}

	/**
	 * Run one importer.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_migrator( WP_REST_Request $request ) {
		$id       = (string) $request->get_param( 'id' );
		$migrator = MigratorRegistry::get( $id );

		if ( null === $migrator || ! $migrator->is_available() ) {
			return Routes::error( 'lw_pixel_unknown_migrator', __( 'This import source is not available.', 'lw-pixel' ), 404 );
		}

		$result = $migrator->run();

		return new WP_REST_Response(
			[
				'id'      => $id,
				'updated' => array_values( array_map( 'strval', $result['updated'] ) ),
				'skipped' => array_values( array_map( 'strval', $result['skipped'] ) ),
			]
		);
	}

	/**
	 * REST shape of one importer; secrets in the preview only say whether
	 * a value is set.
	 *
	 * @param string            $id       Registry ID.
	 * @param MigratorInterface $migrator Importer.
	 * @return array<string, mixed>
	 */
	private static function present( string $id, MigratorInterface $migrator ): array {
		$available = $migrator->is_available();
		$rows      = [];

		foreach ( $available ? $migrator->preview() : [] as $key => $change ) {
			$secret = in_array( $key, Options::SECRET_KEYS, true );
			$rows[] = [
				'key'    => (string) $key,
				'secret' => $secret,
				'from'   => $secret ? ! empty( $change['from'] ) : $change['from'],
				'to'     => $secret ? ! empty( $change['to'] ) : $change['to'],
			];
		}

		return [
			'id'        => $id,
			'label'     => $migrator->get_label(),
			'available' => $available,
			'preview'   => $rows,
		];
	}
}

<?php
/**
 * Custom events REST controller.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Rest\Admin\CustomEvents;

use LightweightPlugins\Pixel\CustomEvents\MetaBoxes;
use LightweightPlugins\Pixel\Rest\Admin\Routes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * List, create, read, update and delete custom events
 * (lw-pixel/v1/admin/custom-events).
 */
final class CustomEventsController {

	/**
	 * Largest accepted request body, in bytes.
	 */
	private const MAX_BYTES = 16384;

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		Routes::add(
			'/admin/custom-events',
			[
				WP_REST_Server::READABLE  => 'list_events',
				WP_REST_Server::CREATABLE => 'create_event',
			],
			$this
		);

		Routes::add(
			'/admin/custom-events/(?P<id>\d+)',
			[
				WP_REST_Server::READABLE  => 'get_event',
				WP_REST_Server::CREATABLE => 'update_event',
				'DELETE'                  => 'delete_event',
			],
			$this
		);
	}

	/**
	 * Every event.
	 *
	 * @return WP_REST_Response
	 */
	public function list_events(): WP_REST_Response {
		return new WP_REST_Response(
			[
				'events' => CustomEventStore::all(),
				'meta'   => [
					'defaults'   => MetaBoxes::defaults(),
					'triggers'   => CustomEventInput::TRIGGERS,
					'run_limit'  => 100,
					'max_listed' => CustomEventStore::MAX_LISTED,
				],
			]
		);
	}

	/**
	 * One event.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_event( WP_REST_Request $request ) {
		$post = CustomEventStore::find( (int) $request->get_param( 'id' ) );

		return null === $post ? self::not_found() : new WP_REST_Response( CustomEventStore::present( $post ) );
	}

	/**
	 * Create an event (enabled unless `enabled: false` is sent).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_event( WP_REST_Request $request ) {
		return $this->write( $request, null );
	}

	/**
	 * Partial update of an event.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_event( WP_REST_Request $request ) {
		$post = CustomEventStore::find( (int) $request->get_param( 'id' ) );

		return null === $post ? self::not_found() : $this->write( $request, $post );
	}

	/**
	 * Move an event to the trash.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_event( WP_REST_Request $request ) {
		$post = CustomEventStore::find( (int) $request->get_param( 'id' ) );

		if ( null === $post ) {
			return self::not_found();
		}

		if ( ! wp_trash_post( $post->ID ) ) {
			return Routes::error( 'lw_pixel_delete_failed', __( 'The event could not be deleted.', 'lw-pixel' ), 500 );
		}

		return new WP_REST_Response(
			[
				'deleted' => true,
				'id'      => $post->ID,
			]
		);
	}

	/**
	 * Validate and write.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param \WP_Post|null   $post    Existing post, or null to create.
	 * @return WP_REST_Response|WP_Error
	 */
	private function write( WP_REST_Request $request, ?\WP_Post $post ) {
		$too_large = Routes::too_large( (string) $request->get_body(), self::MAX_BYTES );

		if ( null !== $too_large ) {
			return $too_large;
		}

		$input = new CustomEventInput( Routes::body( $request ), null === $post ? null : MetaBoxes::get_data( $post->ID ) );

		if ( [] !== $input->errors() ) {
			return Routes::error(
				'lw_pixel_invalid',
				__( 'Some fields are not valid. Nothing was saved.', 'lw-pixel' ),
				400,
				[ 'fields' => $input->errors() ]
			);
		}

		$id = CustomEventStore::write( $input, $post );

		if ( is_wp_error( $id ) ) {
			return Routes::error( 'lw_pixel_save_failed', __( 'The event could not be saved.', 'lw-pixel' ), 500 );
		}

		$saved = CustomEventStore::find( $id );

		return null === $saved ? self::not_found() : new WP_REST_Response( CustomEventStore::present( $saved ), null === $post ? 201 : 200 );
	}

	/**
	 * The "no such event" error.
	 *
	 * @return WP_Error
	 */
	private static function not_found(): WP_Error {
		return Routes::error( 'lw_pixel_not_found', __( 'This custom event does not exist.', 'lw-pixel' ), 404 );
	}
}

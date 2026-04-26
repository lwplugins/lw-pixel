<?php
/**
 * ViewContent event for singular posts/pages/CPTs.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Events;

/**
 * Fires on singular templates (post, page, custom post types).
 */
final class ViewContent extends AbstractEvent {

	public function __construct() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();

		if ( ! $post ) {
			return;
		}

		$this->params = [
			'content_id'   => (string) $post->ID,
			'content_name' => get_the_title( $post ),
			'content_type' => $post->post_type,
		];
	}

	public function get_name(): string {
		return 'ViewContent';
	}

	public function should_fire(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		// WooCommerce handles its own product ViewContent.
		if ( function_exists( 'is_product' ) && is_product() ) {
			return false;
		}

		return parent::should_fire();
	}

	protected function option_key(): string {
		return 'event_view_content';
	}
}

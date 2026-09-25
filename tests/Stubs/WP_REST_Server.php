<?php
/**
 * Minimal WP_REST_Server double for unit tests (WordPress is not loaded).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

// phpcs:ignoreFile -- test double mirroring a WordPress core class name.

class WP_REST_Server {
	const READABLE  = 'GET';
	const CREATABLE = 'POST';
}

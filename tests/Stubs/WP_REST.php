<?php
/**
 * Minimal WP_REST_Request / WP_REST_Response doubles for unit tests.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

// phpcs:ignoreFile -- test doubles mirroring WordPress core class names.

class WP_REST_Request {

	/**
	 * @var array<string, mixed>
	 */
	private array $params;

	private string $body;

	public function __construct( array $params = [], string $body = '' ) {
		$this->params = $params;
		$this->body   = '' === $body ? (string) json_encode( $params ) : $body;
	}

	public function get_param( string $key ) {
		return $this->params[ $key ] ?? null;
	}

	public function get_body(): string {
		return $this->body;
	}

	public function get_json_params() {
		$body = json_decode( $this->body, true );

		return is_array( $body ) ? $body : null;
	}

	public function set_param( string $key, $value ): void {
		$this->params[ $key ] = $value;
	}

	public function get_body_params(): array {
		return [];
	}
}

class WP_REST_Response {

	/**
	 * @var mixed
	 */
	private $data;

	private int $status;

	public function __construct( $data = null, int $status = 200 ) {
		$this->data   = $data;
		$this->status = $status;
	}

	public function get_status(): int {
		return $this->status;
	}

	public function get_data() {
		return $this->data;
	}
}

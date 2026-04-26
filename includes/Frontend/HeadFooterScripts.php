<?php
/**
 * Head / Body / Footer custom script injection.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Frontend;

use LightweightPlugins\Pixel\Options;

/**
 * Outputs raw scripts the user added in the Advanced settings tab.
 */
final class HeadFooterScripts {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'render_head' ], 99 );
		add_action( 'wp_body_open', [ $this, 'render_body_open' ] );
		add_action( 'wp_footer', [ $this, 'render_footer' ], 99 );
	}

	/**
	 * Render head code.
	 *
	 * @return void
	 */
	public function render_head(): void {
		$this->render( (string) Options::get( 'head_code', '' ) );
	}

	/**
	 * Render body-open code.
	 *
	 * @return void
	 */
	public function render_body_open(): void {
		$this->render( (string) Options::get( 'body_open_code', '' ) );
	}

	/**
	 * Render footer code.
	 *
	 * @return void
	 */
	public function render_footer(): void {
		$this->render( (string) Options::get( 'footer_code', '' ) );
	}

	/**
	 * Render a raw script blob.
	 *
	 * @param string $code Raw script.
	 * @return void
	 */
	private function render( string $code ): void {
		$code = trim( $code );

		if ( '' === $code ) {
			return;
		}

		echo $code; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- intentional raw script.
		echo "\n";
	}
}

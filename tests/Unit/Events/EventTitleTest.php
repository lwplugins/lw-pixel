<?php
/**
 * Titles go to the providers as plain text, not HTML-encoded (issue #32).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Events;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Events\PageView;
use LightweightPlugins\Pixel\Events\ViewContent;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Events\PageView
 * @covers \LightweightPlugins\Pixel\Events\ViewContent
 * @covers \LightweightPlugins\Pixel\Events\AbstractEvent
 */
final class EventTitleTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_strip_all_tags' )->alias( static fn ( string $text ): string => trim( strip_tags( $text ) ) );
	}

	public function test_page_view_title_is_decoded(): void {
		// wp_get_document_title() runs wptexturize, convert_chars and esc_html.
		Functions\when( 'wp_get_document_title' )->justReturn( 'Kos&#8217;r &#8211; Bolt &amp; Társa &#8220;2&#8221;' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );

		$params = ( new PageView() )->get_params();

		$this->assertSame( 'Kos’r – Bolt & Társa “2”', $params['page_title'] );
	}

	public function test_view_content_name_is_decoded_and_untagged(): void {
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_post' )->justReturn( (object) [ 'ID' => 7, 'post_type' => 'post' ] );
		// get_the_title() runs wptexturize and convert_chars; titles may hold markup.
		Functions\when( 'get_the_title' )->justReturn( 'Anna&#8217;s <em>new</em> post &#8211; part 2' );

		$params = ( new ViewContent() )->get_params();

		$this->assertSame( 'Anna’s new post – part 2', $params['content_name'] );
	}
}

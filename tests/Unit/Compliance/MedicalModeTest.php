<?php
/**
 * Tests for medical mode on the server-side providers.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Compliance;

use LightweightPlugins\Pixel\Compliance\MedicalMode;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\Compliance\MedicalMode
 */
final class MedicalModeTest extends TestCase {

	public function test_chatgpt_event_keeps_only_the_browser_reference(): void {
		$event = MedicalMode::strip_chatgpt_event(
			[
				'user' => [
					'ip_address'    => '203.0.113.7',
					'user_agent'    => 'UA',
					'obref'         => 'ob.1',
					'emails_sha256' => [ 'x' ],
				],
				'data' => [ 'contents' => [ [ 'id' => '1', 'name' => 'Test kit' ] ] ],
			]
		);

		$this->assertSame( [ 'obref' => 'ob.1' ], $event['user'] );
		$this->assertSame( [ 'id' => '1' ], $event['data']['contents'][0] );
	}

	public function test_meta_event_drops_content_name_and_search_term(): void {
		$event = MedicalMode::strip_meta_event(
			[
				'event_name'  => 'Lead',
				'custom_data' => [
					'content_name'  => 'Cancer screening form',
					'search_string' => 'hiv test',
					'value'         => 1.0,
				],
			]
		);

		$this->assertSame( [ 'value' => 1.0 ], $event['custom_data'] );
	}

	public function test_ga4_event_drops_url_search_and_item_names(): void {
		$event = MedicalMode::strip_ga4_event(
			[
				'name'   => 'purchase',
				'params' => [
					'page_location' => 'https://x.test/',
					'items'         => [ [ 'item_id' => '1', 'item_name' => 'Test kit' ] ],
				],
			]
		);

		$this->assertSame( [ 'items' => [ [ 'item_id' => '1' ] ] ], $event['params'] );
	}
}

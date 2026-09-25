<?php
/**
 * Tests for the custom event write validation.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Rest\Admin\CustomEvents;

use LightweightPlugins\Pixel\CustomEvents\MetaBoxes;
use LightweightPlugins\Pixel\Rest\Admin\CustomEvents\CustomEventInput;
use LightweightPlugins\Pixel\Tests\Unit\Rest\Admin\RestTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Rest\Admin\CustomEvents\CustomEventInput
 */
final class CustomEventInputTest extends RestTestCase {

	public function test_a_new_event_needs_a_name(): void {
		$this->assertArrayHasKey( 'event_name', ( new CustomEventInput( [], null ) )->errors() );
	}

	public function test_a_valid_new_event_is_merged_over_the_defaults(): void {
		$input = new CustomEventInput(
			[
				'event_name' => 'SignupClick',
				'currency'   => 'huf',
				'value'      => '9.99',
			],
			null
		);

		$this->assertSame( [], $input->errors() );
		$this->assertSame( 'HUF', $input->data()['currency'] );
		$this->assertSame( 50, $input->data()['scroll_pct'] );
	}

	/**
	 * @dataProvider provide_bad_names
	 */
	public function test_event_names_must_suit_every_platform( string $name ): void {
		$this->assertArrayHasKey( 'event_name', ( new CustomEventInput( [ 'event_name' => $name ], null ) )->errors() );
	}

	public static function provide_bad_names(): array {
		return [
			'space'        => [ 'My Event' ],
			'digit first'  => [ '1Event' ],
			'too long'     => [ str_repeat( 'a', 41 ) ],
			'script'       => [ '<script>' ],
		];
	}

	public function test_a_click_trigger_needs_a_selector(): void {
		$input = new CustomEventInput(
			[
				'event_name'   => 'CtaClick',
				'trigger_type' => 'click',
			],
			null
		);

		$this->assertArrayHasKey( 'selector', $input->errors() );
	}

	public function test_a_page_pattern_must_be_a_path(): void {
		$input = new CustomEventInput(
			[
				'event_name'   => 'Ok',
				'page_pattern' => 'products',
			],
			null
		);

		$this->assertArrayHasKey( 'page_pattern', $input->errors() );
	}

	public function test_scroll_depth_is_limited_to_100(): void {
		$this->assertArrayHasKey( 'scroll_pct', ( new CustomEventInput( [ 'scroll_pct' => 150 ], MetaBoxes::defaults() ) )->errors() );
	}

	public function test_an_older_event_can_be_switched_off_without_revalidating_its_name(): void {
		$legacy = array_merge( MetaBoxes::defaults(), [ 'event_name' => 'Old event name' ] );

		$input = new CustomEventInput( [ 'enabled' => false ], $legacy );

		$this->assertSame( [], $input->errors() );
		$this->assertSame( [ 'enabled' => false ], $input->post() );
	}

	public function test_unknown_fields_are_reported(): void {
		$this->assertArrayHasKey( 'post_author', ( new CustomEventInput( [ 'post_author' => 1 ], MetaBoxes::defaults() ) )->errors() );
	}

	public function test_selector_backslashes_survive(): void {
		$input = new CustomEventInput(
			[
				'event_name'   => 'Click',
				'trigger_type' => 'click',
				'selector'     => '.md\:hidden > a',
			],
			null
		);

		$this->assertSame( '.md\:hidden > a', $input->data()['selector'] );
	}
}

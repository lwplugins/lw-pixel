<?php
/**
 * Tests for the form-plugin payload adapters.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Forms;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Forms\FormHandlers;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Pixel\Forms\FormHandlers
 */
final class FormHandlersTest extends MonkeyTestCase {

	/**
	 * Elementor Pro's Form_Record::get_form_settings( $key ) returns one
	 * setting; asking for '' returned null, so id/name were always empty.
	 */
	public function test_elementor_reads_the_form_id_and_name_settings(): void {
		$record = new class() {
			public function get_form_settings( $key ) {
				$settings = [
					'id'        => 'a1b2c3d',
					'form_name' => 'Quote request',
				];
				return $settings[ $key ] ?? null;
			}
		};

		$this->assertSame(
			[
				'id'     => 'a1b2c3d',
				'name'   => 'Quote request',
				'source' => 'elementor',
			],
			FormHandlers::elementor( $record )
		);
	}

	public function test_forminator_uses_the_form_id_and_its_post_title(): void {
		Functions\expect( 'get_post_field' )->once()->with( 'post_title', 12 )->andReturn( 'Newsletter' );

		$this->assertSame(
			[
				'id'     => 12,
				'name'   => 'Newsletter',
				'source' => 'forminator',
			],
			FormHandlers::forminator( 12 )
		);
	}

	public function test_wsform_reads_the_submit_objects_form(): void {
		$submit              = new \stdClass();
		$submit->form_id     = '4';
		$submit->form_object = (object) [ 'label' => 'Booking' ];

		$this->assertSame(
			[
				'id'     => 4,
				'name'   => 'Booking',
				'source' => 'wsform',
			],
			FormHandlers::wsform( $submit )
		);
	}
}

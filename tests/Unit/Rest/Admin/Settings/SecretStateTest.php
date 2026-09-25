<?php
/**
 * Tests for the masked secret state.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Rest\Admin\Settings;

use LightweightPlugins\Pixel\Rest\Admin\Settings\SecretState;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\Rest\Admin\Settings\SecretState
 */
final class SecretStateTest extends TestCase {

	public function test_a_stored_secret_shows_only_its_last_four_characters(): void {
		$this->assertSame(
			[
				'set'    => true,
				'source' => 'option',
				'hint'   => '…wxyz',
			],
			SecretState::describe( null, 'EAAB1234567890abcdefwxyz' )
		);
	}

	public function test_a_short_secret_shows_no_characters(): void {
		$this->assertSame( '…', SecretState::hint( 'short' ) );
	}

	public function test_the_constant_wins_over_the_stored_secret(): void {
		$this->assertSame( 'constant', SecretState::describe( 'sk-live-1234567890abcd', 'stored' )['source'] );
	}

	public function test_nothing_set_reports_none(): void {
		$this->assertSame(
			[
				'set'    => false,
				'source' => 'none',
				'hint'   => '',
			],
			SecretState::describe( null, '  ' )
		);
	}
}

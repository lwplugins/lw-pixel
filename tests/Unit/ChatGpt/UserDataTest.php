<?php
/**
 * Tests for ChatGPT Ads customer-data normalisation.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\ChatGpt;

use LightweightPlugins\Pixel\ChatGpt\UserData;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Pixel\ChatGpt\UserData
 */
final class UserDataTest extends TestCase {

	public function test_normalization_rules(): void {
		$fields = UserData::normalized(
			[
				'email'        => '  John.Doe@Example.COM ',
				'phone'        => '06 (30) 123-4567',
				'calling_code' => '+36',
				'external_id'  => ' User-42 ',
				'first_name'   => " Mary-Ann O'Neil ",
				'last_name'    => 'Érdi',
				'city'         => ' Budapest ',
				'country'      => 'hu',
				'postal_code'  => '1011',
			]
		);

		$this->assertSame( 'john.doe@example.com', $fields['email'] );
		$this->assertSame( '36301234567', $fields['phone'] );
		$this->assertSame( 'User-42', $fields['external_id'] );
		$this->assertSame( 'maryannoneil', $fields['first_name'] );
		$this->assertSame( 'érdi', $fields['last_name'] );
		$this->assertSame( 'budapest', $fields['city'] );
		$this->assertSame( 'HU', $fields['country'] );
	}

	public function test_capi_user_hashes_identifiers_and_keeps_geo_plain(): void {
		$user = UserData::for_capi(
			[
				'email'   => 'a@b.co',
				'country' => 'US',
			]
		);

		$this->assertSame( [ hash( 'sha256', 'a@b.co' ) ], $user['emails_sha256'] );
		$this->assertSame( [ 'US' ], $user['countries'] );
		$this->assertArrayNotHasKey( 'phone_numbers_sha256', $user );
	}

	public function test_browser_user_uses_singular_keys(): void {
		$user = UserData::for_browser(
			[
				'email'        => 'a@b.co',
				'phone'        => '+1 415 555 0100',
				'calling_code' => '',
			]
		);

		$this->assertSame( hash( 'sha256', 'a@b.co' ), $user['email_sha256'] );
		$this->assertSame( hash( 'sha256', '14155550100' ), $user['phone_number_sha256'] );
	}

	public function test_invalid_email_is_dropped(): void {
		$this->assertSame( [], UserData::for_capi( [ 'email' => 'not-an-email' ] ) );
	}
}

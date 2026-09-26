<?php
/**
 * Checks the synced LW Plugins hub copy (lwplugins/admin-hub).
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Pixel\Admin\Hub\Assets;
use LightweightPlugins\Pixel\Admin\Hub\Hub;
use LightweightPlugins\Pixel\Admin\Hub\RegistryFallback;
use LightweightPlugins\Pixel\Admin\ParentPage;
use LightweightPlugins\Pixel\Tests\Unit\MonkeyTestCase;

/**
 * The hub logic itself is tested in the admin-hub repo; these tests prove the
 * copy landed in this plugin's namespace, text domain and asset layout.
 */
final class HubIntegrationTest extends MonkeyTestCase {

	/**
	 * Plugin root.
	 *
	 * @return string
	 */
	private function root(): string {
		return dirname( __DIR__, 3 ) . '/';
	}

	protected function tearDown(): void {
		Hub::reset();
		parent::tearDown();
	}

	public function test_candidate_is_this_plugins_copy(): void {
		Hub::init( $this->root() . 'lw-pixel.php' );

		$candidate = Hub::add_candidate( [] )[0];

		$this->assertSame( 'LightweightPlugins\\Pixel\\Admin\\Hub\\Hub', $candidate['class'] );
		$this->assertSame( Hub::VERSION, $candidate['version'] );
	}

	public function test_legacy_parent_page_api_is_kept(): void {
		Functions\expect( 'get_transient' )->once()->andReturn( [ 'lw-pixel' => [ 'name' => 'LW Pixel' ] ] );
		Functions\stubTranslationFunctions();

		$this->assertSame( 'lw-plugins', ParentPage::SLUG );
		$this->assertSame( [ 'lw-pixel' ], array_keys( ParentPage::get_plugins_registry() ) );
	}

	public function test_bundled_assets_use_this_text_domain(): void {
		$dir = $this->root() . Assets::DIR . '/';
		$js  = (string) file_get_contents( $dir . 'index.js' );

		$this->assertFileExists( $dir . 'index.css' );
		$this->assertFileExists( $dir . 'index.asset.json' );
		$this->assertStringContainsString( '"lw-pixel"', $js );
		$this->assertStringNotContainsString( 'lw-admin-hub', $js );
		$this->assertFileExists( $this->root() . 'languages/lw-pixel-hu_HU-' . md5( Assets::DIR . '/index.js' ) . '.json' );
	}

	public function test_every_bundled_registry_plugin_has_an_icon(): void {
		Functions\stubTranslationFunctions();

		foreach ( array_keys( RegistryFallback::get() ) as $slug ) {
			$this->assertFileExists( $this->root() . Assets::DIR . '/icons/' . $slug . '.svg' );
		}
	}
}

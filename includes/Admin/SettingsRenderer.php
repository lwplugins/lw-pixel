<?php
/**
 * Settings page HTML renderer.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin;

use LightweightPlugins\Pixel\Admin\Settings\TabInterface;

/**
 * Renders the settings page tabs and content.
 */
final class SettingsRenderer {

	/**
	 * Tabs.
	 *
	 * @var array<int, TabInterface>
	 */
	private array $tabs;

	/**
	 * Constructor.
	 *
	 * @param array<int, TabInterface> $tabs Settings tabs.
	 */
	public function __construct( array $tabs ) {
		$this->tabs = $tabs;
	}

	/**
	 * Render the entire page.
	 *
	 * @param string $settings_group Registered settings group.
	 * @return void
	 */
	public function render_page( string $settings_group ): void {
		?>
		<div class="wrap">
			<h1>
				<img src="<?php echo esc_url( LW_PIXEL_URL . 'assets/img/title-icon.svg' ); ?>" alt="" class="lw-title-icon" />
				<?php esc_html_e( 'Lightweight Pixel', 'lw-pixel' ); ?>
				<span style="font-size: 13px; font-weight: 400; color: #888;">(<?php echo esc_html( LW_PIXEL_VERSION ); ?>)</span>
			</h1>

			<form method="post" action="options.php">
				<?php settings_fields( $settings_group ); ?>

				<div class="lw-pixel-settings">
					<?php $this->render_nav(); ?>

					<div class="lw-pixel-tab-content">
						<?php $this->render_panels(); ?>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the tab navigation list.
	 *
	 * @return void
	 */
	private function render_nav(): void {
		?>
		<ul class="lw-pixel-tabs">
			<?php foreach ( $this->tabs as $index => $tab ) : ?>
				<li>
					<a href="#<?php echo esc_attr( $tab->get_slug() ); ?>" <?php echo 0 === $index ? 'class="active"' : ''; ?>>
						<span class="dashicons <?php echo esc_attr( $tab->get_icon() ); ?>"></span>
						<?php echo esc_html( $tab->get_label() ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Render every tab panel.
	 *
	 * @return void
	 */
	private function render_panels(): void {
		foreach ( $this->tabs as $index => $tab ) {
			$active_class = 0 === $index ? ' active' : '';
			printf(
				'<div id="tab-%s" class="lw-pixel-tab-panel%s">',
				esc_attr( $tab->get_slug() ),
				esc_attr( $active_class )
			);
			$tab->render();
			if ( $this->should_show_save_button( $tab ) ) {
				submit_button();
			}
			echo '</div>';
		}
	}

	/**
	 * Whether a tab should render the WordPress save button.
	 *
	 * Tools (migrator launchers) and System Report (read-only diagnostics)
	 * have no editable fields, so a save button would be misleading there.
	 *
	 * @param TabInterface $tab Tab being rendered.
	 * @return bool
	 */
	private function should_show_save_button( TabInterface $tab ): bool {
		return ! in_array( $tab->get_slug(), [ 'tools', 'system-report' ], true );
	}
}

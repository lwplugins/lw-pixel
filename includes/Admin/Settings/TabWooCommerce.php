<?php
/**
 * WooCommerce settings tab.
 *
 * @package LightweightPlugins\Pixel
 */

declare(strict_types=1);

namespace LightweightPlugins\Pixel\Admin\Settings;

/**
 * Renders the WooCommerce event configuration.
 */
final class TabWooCommerce implements TabInterface {

	use FieldRendererTrait;

	public function get_slug(): string {
		return 'woocommerce';
	}

	public function get_label(): string {
		return __( 'WooCommerce', 'lw-pixel' );
	}

	public function get_icon(): string {
		return 'dashicons-cart';
	}

	public function render(): void {
		if ( ! class_exists( '\\WooCommerce' ) ) {
			?>
			<h2><?php esc_html_e( 'WooCommerce', 'lw-pixel' ); ?></h2>
			<div class="notice notice-warning lw-notice inline"><p>
				<?php esc_html_e( 'WooCommerce is not active. Activate it to enable ecommerce events.', 'lw-pixel' ); ?>
			</p></div>
			<?php
			return;
		}

		?>
		<h2><?php esc_html_e( 'WooCommerce Events', 'lw-pixel' ); ?></h2>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'View Product', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_view_product',
						'label' => __( 'Fire ViewContent on product pages', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'View Category', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_view_category',
						'label' => __( 'Fire ViewCategory on category archives', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'View Cart', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_view_cart',
						'label' => __( 'Fire ViewCart on the cart page', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Add to Cart', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_add_to_cart',
						'label' => __( 'Fire AddToCart when products are added', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Initiate Checkout', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_initiate_checkout',
						'label' => __( 'Fire InitiateCheckout on the checkout page', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Add Payment Info', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_add_payment_info',
						'label' => __( 'Fire AddPaymentInfo when payment method is selected', 'lw-pixel' ),
					]
				);
				?>
					</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Purchase', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_purchase',
						'label' => __( 'Fire Purchase on the order-received page', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Product data', 'lw-pixel' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Use SKU', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_use_sku',
						'label' => __( 'Use product SKU as content_id (instead of post ID)', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="woo_content_id_prefix"><?php esc_html_e( 'Content ID prefix', 'lw-pixel' ); ?></label></th>
				<td>
				<?php
				$this->render_text_field(
					[
						'name'        => 'woo_content_id_prefix',
						'description' => __( 'Optional prefix for content_id (e.g. "wc_post_id_").', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Send value with tax', 'lw-pixel' ); ?></th>
				<td>
				<?php
				$this->render_checkbox_field(
					[
						'name'  => 'woo_send_value_with_tax',
						'label' => __( 'Include tax in event value', 'lw-pixel' ),
					]
				);
				?>
				</td>
			</tr>
		</table>
		<?php
	}
}

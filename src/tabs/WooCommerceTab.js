/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import { TextRow } from '../components/Fields';
import Section from '../components/Section';
import { OptionSwitch, SwitchList } from '../components/Switches';

/**
 * WooCommerce: ecommerce events and product data. Editable even while
 * WooCommerce is inactive (the settings are kept for when it is activated).
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function WooCommerceTab( { store } ) {
	const { meta } = store.data;
	const sw = ( name, title, help ) => (
		<OptionSwitch
			store={ store }
			name={ name }
			title={ title }
			help={ help }
		/>
	);

	return (
		<>
			{ ! meta.wooActive && (
				<Callout tone="warning">
					{ __(
						'WooCommerce is not active. Activate it to enable ecommerce events. Your settings here are kept until then.',
						'lw-pixel'
					) }
				</Callout>
			) }
			<Section title={ __( 'Ecommerce events', 'lw-pixel' ) }>
				<SwitchList>
					{ sw(
						'woo_view_product',
						__( 'View Product', 'lw-pixel' ),
						__( 'Fire ViewContent on product pages', 'lw-pixel' )
					) }
					{ sw(
						'woo_view_category',
						__( 'View Category', 'lw-pixel' ),
						__(
							'Fire ViewCategory on category archives',
							'lw-pixel'
						)
					) }
					{ sw(
						'woo_view_cart',
						__( 'View Cart', 'lw-pixel' ),
						__( 'Fire ViewCart on the cart page', 'lw-pixel' )
					) }
					{ sw(
						'woo_add_to_cart',
						__( 'Add to Cart', 'lw-pixel' ),
						__(
							'Fire AddToCart when products are added',
							'lw-pixel'
						)
					) }
					{ sw(
						'woo_initiate_checkout',
						__( 'Initiate Checkout', 'lw-pixel' ),
						__(
							'Fire InitiateCheckout on the checkout page',
							'lw-pixel'
						)
					) }
					{ sw(
						'woo_add_payment_info',
						__( 'Add Payment Info', 'lw-pixel' ),
						__(
							'Fire AddPaymentInfo when payment method is selected',
							'lw-pixel'
						)
					) }
					{ sw(
						'woo_purchase',
						__( 'Purchase', 'lw-pixel' ),
						__(
							'Fire Purchase on the order-received page',
							'lw-pixel'
						)
					) }
				</SwitchList>
			</Section>
			<Section title={ __( 'Product data', 'lw-pixel' ) }>
				<SwitchList>
					{ sw(
						'woo_use_sku',
						__( 'Use SKU', 'lw-pixel' ),
						__(
							'Use product SKU as content_id (instead of post ID)',
							'lw-pixel'
						)
					) }
					{ sw(
						'woo_send_value_with_tax',
						__( 'Include tax in event value', 'lw-pixel' )
					) }
				</SwitchList>
				<TextRow
					store={ store }
					name="woo_content_id_prefix"
					title={ __( 'Content ID prefix', 'lw-pixel' ) }
					help={ __(
						'Optional prefix for content_id (e.g. "wc_post_id_").',
						'lw-pixel'
					) }
					mono
				/>
			</Section>
		</>
	);
}

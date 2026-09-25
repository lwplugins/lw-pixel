/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { cloudUpload, people } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import { TextRow } from '../components/Fields';
import ProviderCard, { stateBadge } from '../components/ProviderCard';
import SecretRow from '../components/SecretRow';
import { OptionSwitch, SwitchList } from '../components/Switches';
import { hasSecret } from './providerState';

/**
 * Meta: the browser pixel and the Conversions API (server-side).
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function MetaTab( { store } ) {
	const { options, meta } = store.data;

	return (
		<>
			<ProviderCard
				store={ store }
				name="fb_enabled"
				title={ __( 'Meta Pixel', 'lw-pixel' ) }
				icon={ people }
				badge={ stateBadge(
					options.fb_enabled,
					!! options.fb_pixel_id,
					__( 'Pixel ID missing', 'lw-pixel' )
				) }
				description={ __(
					'Loads the Meta (Facebook, Instagram) pixel and sends your events to it.',
					'lw-pixel'
				) }
			>
				<TextRow
					store={ store }
					name="fb_pixel_id"
					title={ __( 'Pixel ID', 'lw-pixel' ) }
					help={ __( 'Your Meta Pixel ID (numeric).', 'lw-pixel' ) }
					placeholder="1234567890"
					mono
				/>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="fb_advanced_matching"
						title={ __(
							'Enable advanced matching for logged-in users',
							'lw-pixel'
						) }
						help={ __(
							'Sends hashed email/phone to improve match rate.',
							'lw-pixel'
						) }
					/>
				</SwitchList>
			</ProviderCard>
			<ProviderCard
				store={ store }
				name="fb_capi_enabled"
				title={ __( 'Conversion API (server-side)', 'lw-pixel' ) }
				icon={ cloudUpload }
				badge={ stateBadge(
					options.fb_capi_enabled,
					hasSecret( store, 'fb_capi_token' ) &&
						!! options.fb_pixel_id,
					__( 'Token or pixel ID missing', 'lw-pixel' )
				) }
				description={ __(
					'Send events server-side via Conversion API',
					'lw-pixel'
				) }
			>
				<SecretRow
					store={ store }
					name="fb_capi_token"
					title={ __( 'Access Token', 'lw-pixel' ) }
					help={ __(
						'Generate in Events Manager → Settings → Conversion API.',
						'lw-pixel'
					) }
					placeholder="EAAB..."
				/>
				<TextRow
					store={ store }
					name="fb_test_event_code"
					title={ __( 'Test event code', 'lw-pixel' ) }
					help={ __(
						'Optional. Use to test events in Events Manager → Test Events.',
						'lw-pixel'
					) }
					placeholder="TEST12345"
					mono
				/>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="fb_send_external_id"
						title={ __(
							'Send external_id (logged-in user ID, hashed) for better matching',
							'lw-pixel'
						) }
					/>
					<OptionSwitch
						store={ store }
						name="fb_order_enrich"
						title={ __(
							'Re-send Purchase to CAPI when WooCommerce order completes/processes',
							'lw-pixel'
						) }
						help={ __(
							'Captures the final order value after payment confirmation.',
							'lw-pixel'
						) }
					/>
				</SwitchList>
				{ ! meta.wooActive && options.fb_order_enrich && (
					<Callout tone="warning">
						{ __(
							'WooCommerce is not active, so there are no orders to re-send.',
							'lw-pixel'
						) }
					</Callout>
				) }
				{ ! options.fb_pixel_id && (
					<Callout>
						{ __(
							'The Conversion API needs the Pixel ID above too, even when the browser pixel is off.',
							'lw-pixel'
						) }
					</Callout>
				) }
			</ProviderCard>
		</>
	);
}

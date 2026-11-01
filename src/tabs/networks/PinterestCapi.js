/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { cloudUpload } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Callout from '../../components/Callout';
import ConnectionTest from '../../components/ConnectionTest';
import { TextRow } from '../../components/Fields';
import ProviderCard, { stateBadge } from '../../components/ProviderCard';
import SecretRow from '../../components/SecretRow';
import { OptionSwitch, SwitchList } from '../../components/Switches';
import { api } from '../../data/api';
import { hasSecret } from '../providerState';

/**
 * Pinterest Conversions API (server-side), under the Pinterest Tag card.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function PinterestCapi( { store } ) {
	const { options } = store.data;

	return (
		<>
			<ProviderCard
				store={ store }
				name="pinterest_capi_enabled"
				title={ __(
					'Pinterest Conversions API (server-side)',
					'lw-pixel'
				) }
				icon={ cloudUpload }
				badge={ stateBadge(
					options.pinterest_capi_enabled,
					hasSecret( store, 'pinterest_capi_token' ) &&
						!! options.pinterest_ad_account_id,
					__( 'Token or ad account ID missing', 'lw-pixel' )
				) }
				description={ __(
					'Also sends purchases, add-to-carts, page visits, leads, searches and sign-ups from your server, deduplicated with the Pinterest Tag.',
					'lw-pixel'
				) }
			>
				<TextRow
					store={ store }
					name="pinterest_ad_account_id"
					title={ __( 'Ad account ID', 'lw-pixel' ) }
					help={ __(
						'The numeric ID of your Pinterest ad account (Ads Manager).',
						'lw-pixel'
					) }
					placeholder="549755885175"
					mono
				/>
				<SecretRow
					store={ store }
					name="pinterest_capi_token"
					title={ __( 'Conversion access token', 'lw-pixel' ) }
					help={ __(
						'Generate it in Ads Manager under Conversions. Stored in the database; you can instead define LW_PIXEL_PINTEREST_TOKEN in wp-config.php (takes precedence).',
						'lw-pixel'
					) }
				/>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="pinterest_advanced_matching"
						title={ __( 'Advanced matching', 'lw-pixel' ) }
						help={ __(
							'Sends SHA-256 hashed email, phone, name, address and user ID (the logged-in user, or the customer’s billing data with purchases) to improve matching.',
							'lw-pixel'
						) }
					/>
					<OptionSwitch
						store={ store }
						name="pinterest_capi_test"
						title={ __( 'Test mode', 'lw-pixel' ) }
						help={ __(
							'Pinterest checks the events but does not record them. Turn off on a live site.',
							'lw-pixel'
						) }
					/>
				</SwitchList>
				{ options.compliance_medical && (
					<Callout tone="warning">
						{ __(
							'Medical mode is on: Pinterest needs the visitor’s IP address and user agent or a hashed email, so no server-side events are sent.',
							'lw-pixel'
						) }
					</Callout>
				) }
			</ProviderCard>
			<ConnectionTest
				store={ store }
				idKey="pinterest_ad_account_id"
				secretKey="pinterest_capi_token"
				test={ api.testPinterest }
				hints={ {
					idle: __(
						'Sends one sample event in test mode: Pinterest checks the ad account ID and the token, and records nothing.',
						'lw-pixel'
					),
					dirty: __(
						'Save your changes first: the test uses the saved ad account ID and token.',
						'lw-pixel'
					),
					empty: __(
						'Save an ad account ID and a conversion access token to run the test.',
						'lw-pixel'
					),
				} }
			/>
		</>
	);
}

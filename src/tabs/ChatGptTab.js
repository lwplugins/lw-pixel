/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { cloudUpload, commentContent } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import { TextRow } from '../components/Fields';
import ProviderCard, { stateBadge } from '../components/ProviderCard';
import SecretRow from '../components/SecretRow';
import StatusBadge from '../components/StatusBadge';
import { OptionSwitch, SwitchList } from '../components/Switches';
import ConnectionTest from './chatgpt/ConnectionTest';
import { hasSecret } from './providerState';

/**
 * ChatGPT Ads: the measurement pixel and the Conversions API, with a
 * validate-only connection test.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function ChatGptTab( { store } ) {
	const { options, meta } = store.data;
	const newBadge = (
		<StatusBadge status="info">{ __( 'New', 'lw-pixel' ) }</StatusBadge>
	);

	return (
		<>
			<ProviderCard
				store={ store }
				name="chatgpt_enabled"
				title={ __( 'ChatGPT Ads pixel', 'lw-pixel' ) }
				icon={ commentContent }
				badge={ stateBadge(
					options.chatgpt_enabled,
					!! options.chatgpt_pixel_id,
					__( 'Pixel ID missing', 'lw-pixel' )
				) }
				extra={ newBadge }
				description={ __(
					'Measure the purchases, sign-ups and leads your ChatGPT ads bring. Loads the ChatGPT Ads measurement pixel in the browser (marketing consent).',
					'lw-pixel'
				) }
			>
				<TextRow
					store={ store }
					name="chatgpt_pixel_id"
					title={ __( 'Pixel ID', 'lw-pixel' ) }
					help={ __(
						'Your ChatGPT Ads pixel ID from the Ads Manager.',
						'lw-pixel'
					) }
					mono
				/>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="chatgpt_advanced_matching"
						title={ __( 'Advanced matching', 'lw-pixel' ) }
						help={ __(
							'Sends SHA-256 hashed email, phone, name and user ID (logged-in users in the browser, the customer’s billing data with server-side purchases) to improve attribution.',
							'lw-pixel'
						) }
					/>
					<OptionSwitch
						store={ store }
						name="chatgpt_debug"
						title={ __( 'Debug mode', 'lw-pixel' ) }
						help={ __(
							'Logs every pixel call in the browser console (test mode). Turn off on a live site.',
							'lw-pixel'
						) }
					/>
				</SwitchList>
			</ProviderCard>
			<ProviderCard
				store={ store }
				name="chatgpt_capi_enabled"
				title={ __( 'Conversions API (server-side)', 'lw-pixel' ) }
				icon={ cloudUpload }
				badge={ stateBadge(
					options.chatgpt_capi_enabled,
					hasSecret( store, 'chatgpt_api_key' ) &&
						!! options.chatgpt_pixel_id,
					__( 'API key or pixel ID missing', 'lw-pixel' )
				) }
				description={ __(
					'Also sends conversions from your server, deduplicated with the browser events.',
					'lw-pixel'
				) }
			>
				<SecretRow
					store={ store }
					name="chatgpt_api_key"
					title={ __( 'Conversions API key', 'lw-pixel' ) }
					help={ __(
						'Create it in the Ads Manager. Stored in the database; you can instead define LW_PIXEL_CHATGPT_API_KEY in wp-config.php (takes precedence).',
						'lw-pixel'
					) }
				/>
				{ ! meta.lwCookieActive && (
					<Callout>
						{ __(
							'Browser and server-side events respect the marketing consent category of LW Cookie. Without a consent plugin they are sent to every visitor.',
							'lw-pixel'
						) }
					</Callout>
				) }
			</ProviderCard>
			<ConnectionTest store={ store } />
		</>
	);
}

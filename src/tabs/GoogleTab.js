/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { chartBar, payment, tag } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { TextRow } from '../components/Fields';
import ProviderCard, { stateBadge } from '../components/ProviderCard';
import SecretRow from '../components/SecretRow';
import { OptionSwitch, SwitchList } from '../components/Switches';
import { upper } from './providerState';

/**
 * Google: Analytics 4 (with the Measurement Protocol), Ads and Tag Manager.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function GoogleTab( { store } ) {
	const { options } = store.data;

	return (
		<>
			<ProviderCard
				store={ store }
				name="ga4_enabled"
				title={ __( 'Google Analytics 4', 'lw-pixel' ) }
				icon={ chartBar }
				badge={ stateBadge(
					options.ga4_enabled,
					!! options.ga4_measurement_id,
					__( 'Measurement ID missing', 'lw-pixel' )
				) }
				description={ __( 'Enable Google Analytics 4', 'lw-pixel' ) }
			>
				<TextRow
					store={ store }
					name="ga4_measurement_id"
					title={ __( 'Measurement ID', 'lw-pixel' ) }
					help={ __(
						'Your GA4 Measurement ID (starts with G-).',
						'lw-pixel'
					) }
					placeholder="G-XXXXXXXXXX"
					normalize={ upper }
					mono
				/>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="ga4_anonymize_ip"
						title={ __( 'Anonymize IP addresses', 'lw-pixel' ) }
					/>
					<OptionSwitch
						store={ store }
						name="ga4_debug"
						title={ __( 'Debug Mode', 'lw-pixel' ) }
						help={ __(
							'Enable debug mode (use Realtime + DebugView)',
							'lw-pixel'
						) }
					/>
					<OptionSwitch
						store={ store }
						name="ga4_mp_enabled"
						title={ __( 'Measurement Protocol', 'lw-pixel' ) }
						help={ __(
							'Send server-side events via the GA4 Measurement Protocol',
							'lw-pixel'
						) }
					/>
				</SwitchList>
				<SecretRow
					store={ store }
					name="ga4_mp_api_secret"
					title={ __( 'MP API Secret', 'lw-pixel' ) }
					help={ __(
						'Generate in GA4 Admin → Data Streams → Measurement Protocol API secrets.',
						'lw-pixel'
					) }
				/>
			</ProviderCard>
			<ProviderCard
				store={ store }
				name="gads_enabled"
				title={ __( 'Google Ads', 'lw-pixel' ) }
				icon={ payment }
				badge={ stateBadge(
					options.gads_enabled,
					!! options.gads_conversion_id,
					__( 'Conversion ID missing', 'lw-pixel' )
				) }
				description={ __(
					'Enable Google Ads conversion tracking',
					'lw-pixel'
				) }
			>
				<TextRow
					store={ store }
					name="gads_conversion_id"
					title={ __( 'Conversion ID', 'lw-pixel' ) }
					help={ __(
						'Your Google Ads Conversion ID (starts with AW-).',
						'lw-pixel'
					) }
					placeholder="AW-1234567890"
					normalize={ upper }
					mono
				/>
				<TextRow
					store={ store }
					name="gads_conversion_label"
					title={ __( 'Conversion label', 'lw-pixel' ) }
					help={ __(
						'Used for the Purchase event conversion.',
						'lw-pixel'
					) }
					mono
				/>
			</ProviderCard>
			<ProviderCard
				store={ store }
				name="gtm_enabled"
				title={ __( 'Google Tag Manager', 'lw-pixel' ) }
				icon={ tag }
				badge={ stateBadge(
					options.gtm_enabled,
					!! options.gtm_container_id,
					__( 'Container ID missing', 'lw-pixel' )
				) }
				description={ __( 'Enable Google Tag Manager', 'lw-pixel' ) }
			>
				<TextRow
					store={ store }
					name="gtm_container_id"
					title={ __( 'Container ID', 'lw-pixel' ) }
					help={ __(
						'Your GTM container ID (starts with GTM-).',
						'lw-pixel'
					) }
					placeholder="GTM-XXXXXXX"
					normalize={ upper }
					mono
				/>
				<SwitchList>
					<OptionSwitch
						store={ store }
						name="gtm_data_layer_only"
						title={ __(
							'Push to dataLayer only (do not load the GTM script)',
							'lw-pixel'
						) }
						help={ __(
							'Useful when the GTM script is loaded by another plugin.',
							'lw-pixel'
						) }
					/>
				</SwitchList>
			</ProviderCard>
		</>
	);
}

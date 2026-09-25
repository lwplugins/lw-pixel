/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import Section from '../components/Section';
import StatTile from '../components/StatTile';
import StatusBadge from '../components/StatusBadge';
import YesNo from '../components/YesNo';

// Pixel ID → the tab that configures it.
const PIXEL_TABS = {
	fb: 'meta',
	ga4: 'google',
	gads: 'google',
	gtm: 'google',
	chatgpt: 'chatgpt',
	tiktok: 'networks',
	pinterest: 'networks',
	bing: 'networks',
	reddit: 'networks',
	snapchat: 'networks',
	x: 'networks',
};

// Integration keys shown under "Detected plugins" (product names).
const PLUGINS = [
	[ 'woocommerce', 'WooCommerce' ],
	[ 'lw_cookie', 'LW Cookie' ],
	[ 'cf7', 'Contact Form 7' ],
	[ 'wpforms', 'WPForms' ],
	[ 'elementor_pro', 'Elementor Pro' ],
	[ 'gravity_forms', 'Gravity Forms' ],
	[ 'forminator', 'Forminator' ],
	[ 'formidable', 'Formidable Forms' ],
	[ 'ninja_forms', 'Ninja Forms' ],
	[ 'fluent_forms', 'Fluent Forms' ],
	[ 'ws_form', 'WS Form' ],
];

/**
 * Status badge of a pixel (saved state).
 *
 * @param {Object} pixel Pixel from meta.pixels.
 * @return {Element} Badge.
 */
function pixelBadge( pixel ) {
	if ( pixel.configured ) {
		return (
			<StatusBadge status="ok">
				{ __( 'Active', 'lw-pixel' ) }
			</StatusBadge>
		);
	}
	if ( pixel.enabled ) {
		return (
			<StatusBadge status="warning">
				{ __( 'ID missing', 'lw-pixel' ) }
			</StatusBadge>
		);
	}
	return <StatusBadge status="idle">{ __( 'Off', 'lw-pixel' ) }</StatusBadge>;
}

/**
 * Overview: what is tracking right now (saved settings), server-side
 * sending, consent and the detected integrations.
 *
 * @param {Object} props
 * @param {Object} props.store  Settings store.
 * @param {Object} props.events Custom events store.
 */
export default function OverviewTab( { store, events } ) {
	const { meta } = store.data;
	const saved = store.saved;
	const active = meta.pixels.filter( ( pixel ) => pixel.configured ).length;
	const server = [
		saved.fb_capi_enabled && __( 'Meta', 'lw-pixel' ),
		saved.ga4_mp_enabled && __( 'GA4', 'lw-pixel' ),
		saved.chatgpt_capi_enabled && __( 'ChatGPT Ads', 'lw-pixel' ),
	].filter( Boolean );
	const customOn = events.list
		? events.list.events.filter( ( event ) => event.enabled ).length
		: null;

	return (
		<>
			{ store.hasEdits && (
				<Callout>
					{ __(
						'This overview shows the saved settings. Save your changes to see them here.',
						'lw-pixel'
					) }
				</Callout>
			) }
			<div className="lw-admin-tiles">
				<StatTile
					label={ __( 'Active pixels', 'lw-pixel' ) }
					value={ `${ active } / ${ meta.pixels.length }` }
					detail={ __( 'Switched on and configured', 'lw-pixel' ) }
				/>
				<StatTile
					label={ __( 'Server-side', 'lw-pixel' ) }
					value={
						server.length
							? server.join( ', ' )
							: __( 'Off', 'lw-pixel' )
					}
					detail={ __(
						'Conversions sent from your server',
						'lw-pixel'
					) }
				/>
				<StatTile
					label={ __( 'Consent', 'lw-pixel' ) }
					value={
						meta.lwCookieActive
							? __( 'LW Cookie', 'lw-pixel' )
							: __( 'Not managed', 'lw-pixel' )
					}
					detail={
						meta.lwCookieActive
							? __( 'Pixels wait for consent', 'lw-pixel' )
							: __( 'Every pixel fires', 'lw-pixel' )
					}
				/>
				<StatTile
					label={ __( 'Custom events', 'lw-pixel' ) }
					value={ customOn === null ? '–' : String( customOn ) }
					detail={
						customOn === null
							? ''
							: sprintf(
									/* translators: %d: number of enabled custom events. */
									_n(
										'%d event switched on',
										'%d events switched on',
										customOn,
										'lw-pixel'
									),
									customOn
								)
					}
				/>
			</div>
			<Section
				title={ __( 'Pixels', 'lw-pixel' ) }
				description={ __(
					'Every ad and analytics platform LW Pixel can send your events to.',
					'lw-pixel'
				) }
			>
				<ul className="lw-px-pixels">
					{ meta.pixels.map( ( pixel ) => (
						<li key={ pixel.id } className="lw-px-pixels__row">
							<span className="lw-px-pixels__name">
								{ pixel.label }
								{ pixel.id === 'chatgpt' && (
									<StatusBadge status="info">
										{ __( 'New', 'lw-pixel' ) }
									</StatusBadge>
								) }
							</span>
							{ pixelBadge( pixel ) }
							{ PIXEL_TABS[ pixel.id ] ? (
								<a href={ `#${ PIXEL_TABS[ pixel.id ] }` }>
									{ __( 'Configure', 'lw-pixel' ) }
									<span className="screen-reader-text">
										{ ` ${ pixel.label }` }
									</span>
								</a>
							) : (
								<span />
							) }
						</li>
					) ) }
				</ul>
			</Section>
			<Section title={ __( 'Detected plugins', 'lw-pixel' ) }>
				<ul className="lw-px-plugins">
					{ PLUGINS.map( ( [ key, label ] ) => (
						<li key={ key }>
							<YesNo
								value={ !! meta.integrations[ key ] }
								label={ label }
							/>
						</li>
					) ) }
				</ul>
			</Section>
		</>
	);
}

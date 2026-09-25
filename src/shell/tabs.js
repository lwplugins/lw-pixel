/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	cart,
	chartBar,
	code,
	commentContent,
	customPostType,
	globe,
	home,
	lock,
	postList,
	reusableBlock,
	shield,
	tool,
	people,
} from '@wordpress/icons';

/**
 * Nav groups, in order. A tab without a group sits above them.
 */
export const GROUPS = [
	{ id: 'pixels', label: __( 'Pixels', 'lw-pixel' ) },
	{ id: 'tracking', label: __( 'Tracking', 'lw-pixel' ) },
	{ id: 'privacy', label: __( 'Privacy', 'lw-pixel' ) },
	{ id: 'settings', label: __( 'Settings', 'lw-pixel' ) },
];

/**
 * Tab registry. `save` = the tab edits lw_pixel_options (top bar Save shown;
 * 'events' = the custom event editor's own save). `fields` / `prefixes` map
 * option keys to the tab, so a failed save can flag the tabs holding an
 * invalid field.
 */
export const TABS = [
	{
		id: 'overview',
		label: __( 'Overview', 'lw-pixel' ),
		title: __( 'Overview', 'lw-pixel' ),
		icon: home,
		save: false,
	},
	{
		id: 'meta',
		group: 'pixels',
		label: __( 'Meta', 'lw-pixel' ),
		title: __( 'Meta Pixel and Conversions API', 'lw-pixel' ),
		icon: people,
		save: true,
		prefixes: [ 'fb_' ],
	},
	{
		id: 'google',
		group: 'pixels',
		label: __( 'Google', 'lw-pixel' ),
		title: __( 'Google Analytics, Ads and Tag Manager', 'lw-pixel' ),
		icon: chartBar,
		save: true,
		prefixes: [ 'ga4_', 'gads_', 'gtm_' ],
	},
	{
		id: 'chatgpt',
		group: 'pixels',
		label: __( 'ChatGPT Ads', 'lw-pixel' ),
		title: __( 'ChatGPT Ads conversion tracking', 'lw-pixel' ),
		icon: commentContent,
		save: true,
		isNew: true,
		prefixes: [ 'chatgpt_' ],
	},
	{
		id: 'networks',
		group: 'pixels',
		label: __( 'Other networks', 'lw-pixel' ),
		title: __( 'Other ad networks', 'lw-pixel' ),
		icon: globe,
		save: true,
		prefixes: [
			'tiktok_',
			'pinterest_',
			'bing_',
			'reddit_',
			'snapchat_',
			'x_',
		],
	},
	{
		id: 'events',
		group: 'tracking',
		label: __( 'Events', 'lw-pixel' ),
		title: __( 'Events', 'lw-pixel' ),
		icon: postList,
		save: true,
		prefixes: [ 'event_' ],
	},
	{
		id: 'forms',
		group: 'tracking',
		label: __( 'Forms', 'lw-pixel' ),
		title: __( 'Form integrations', 'lw-pixel' ),
		icon: reusableBlock,
		save: true,
		prefixes: [ 'form_' ],
	},
	{
		id: 'woocommerce',
		group: 'tracking',
		label: __( 'WooCommerce', 'lw-pixel' ),
		title: __( 'WooCommerce', 'lw-pixel' ),
		icon: cart,
		save: true,
		prefixes: [ 'woo_' ],
	},
	{
		id: 'custom-events',
		group: 'tracking',
		label: __( 'Custom events', 'lw-pixel' ),
		title: __( 'Custom events', 'lw-pixel' ),
		icon: customPostType,
		save: 'events',
	},
	{
		id: 'consent',
		group: 'privacy',
		label: __( 'Consent', 'lw-pixel' ),
		title: __( 'Consent', 'lw-pixel' ),
		icon: lock,
		save: true,
		prefixes: [ 'consent_' ],
	},
	{
		id: 'compliance',
		group: 'privacy',
		label: __( 'Compliance', 'lw-pixel' ),
		title: __( 'Compliance', 'lw-pixel' ),
		icon: shield,
		save: true,
		prefixes: [ 'compliance_' ],
	},
	{
		id: 'advanced',
		group: 'settings',
		label: __( 'Advanced', 'lw-pixel' ),
		title: __( 'Advanced', 'lw-pixel' ),
		icon: code,
		save: true,
		fields: [
			'disable_for_admins',
			'head_code',
			'body_open_code',
			'footer_code',
		],
	},
	{
		id: 'tools',
		group: 'settings',
		label: __( 'Tools', 'lw-pixel' ),
		title: __( 'Tools', 'lw-pixel' ),
		icon: tool,
		save: false,
	},
];

/**
 * Hash slugs of the classic screen, so old links and bookmarks still land
 * on the tab that now holds those settings.
 */
export const ALIASES = {
	facebook: 'meta',
	gtm: 'google',
	tiktok: 'networks',
	pinterest: 'networks',
	bing: 'networks',
	reddit: 'networks',
	snapchat: 'networks',
	x: 'networks',
	'system-report': 'tools',
};

/**
 * Tab id that holds an option key (for error flags in the nav). Unknown
 * keys land on the first settings tab so they are never invisible.
 *
 * @param {string} field Option key.
 * @return {string} Tab id.
 */
export const tabOfField = ( field ) =>
	(
		TABS.find(
			( tab ) =>
				tab.fields?.includes( field ) ||
				tab.prefixes?.some( ( prefix ) => field.startsWith( prefix ) )
		) || TABS[ 1 ]
	).id;

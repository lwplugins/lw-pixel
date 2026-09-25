/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	atSymbol,
	commentAuthorAvatar,
	media,
	mobile,
	pin,
	search,
} from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { TextRow } from '../components/Fields';
import ProviderCard, { stateBadge } from '../components/ProviderCard';

/**
 * The single-ID networks, in the classic order.
 *
 * @return {Object[]} Card definitions.
 */
const networks = () => [
	{
		prefix: 'tiktok',
		idKey: 'tiktok_pixel_id',
		title: __( 'TikTok Pixel', 'lw-pixel' ),
		icon: media,
		idTitle: __( 'Pixel ID', 'lw-pixel' ),
		help: __( 'Your TikTok Pixel ID (from Events Manager).', 'lw-pixel' ),
		placeholder: 'CXXXXXXXXXXXXXX',
	},
	{
		prefix: 'pinterest',
		idKey: 'pinterest_tag_id',
		title: __( 'Pinterest Tag', 'lw-pixel' ),
		icon: pin,
		idTitle: __( 'Tag ID', 'lw-pixel' ),
		help: __( 'Your Pinterest Tag ID.', 'lw-pixel' ),
		placeholder: '2612345678901',
	},
	{
		prefix: 'bing',
		idKey: 'bing_tag_id',
		title: __( 'Microsoft Bing UET', 'lw-pixel' ),
		icon: search,
		idTitle: __( 'UET Tag ID', 'lw-pixel' ),
		help: __( 'Your Bing UET Tag ID.', 'lw-pixel' ),
		placeholder: '12345678',
	},
	{
		prefix: 'reddit',
		idKey: 'reddit_pixel_id',
		title: __( 'Reddit Pixel', 'lw-pixel' ),
		icon: commentAuthorAvatar,
		idTitle: __( 'Advertiser ID', 'lw-pixel' ),
		help: __( 'Your Reddit Advertiser ID.', 'lw-pixel' ),
		placeholder: 't2_xxxxxxx',
	},
	{
		prefix: 'snapchat',
		idKey: 'snapchat_pixel_id',
		title: __( 'Snapchat Pixel', 'lw-pixel' ),
		icon: mobile,
		idTitle: __( 'Pixel ID', 'lw-pixel' ),
		help: __( 'Your Snapchat Pixel ID (UUID format).', 'lw-pixel' ),
		placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
	},
	{
		prefix: 'x',
		idKey: 'x_pixel_id',
		title: __( 'X (Twitter) Pixel', 'lw-pixel' ),
		icon: atSymbol,
		idTitle: __( 'Pixel ID', 'lw-pixel' ),
		help: __( 'Your X Universal Pixel ID.', 'lw-pixel' ),
		placeholder: 'oXXXX',
	},
];

/**
 * Other networks: one card per provider (switch + ID).
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function NetworksTab( { store } ) {
	const { options } = store.data;

	return networks().map( ( net ) => {
		const enabledKey = `${ net.prefix }_enabled`;

		return (
			<ProviderCard
				key={ net.prefix }
				store={ store }
				name={ enabledKey }
				title={ net.title }
				icon={ net.icon }
				badge={ stateBadge(
					options[ enabledKey ],
					!! options[ net.idKey ],
					__( 'ID missing', 'lw-pixel' )
				) }
			>
				<TextRow
					store={ store }
					name={ net.idKey }
					title={ net.idTitle }
					help={ net.help }
					placeholder={ net.placeholder }
					mono
				/>
			</ProviderCard>
		);
	} );
}

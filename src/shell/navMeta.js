/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, caution } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import StatusBadge from '../components/StatusBadge';
import { TABS, tabOfField } from './tabs';

/**
 * Nav extras: a "New" badge (ChatGPT Ads), "Inactive" on WooCommerce while
 * the plugin is not active, and a flag on every tab holding a field the last
 * save rejected (the flag wins).
 *
 * @param {Object}      props
 * @param {Object}      props.errors Field errors { key: [ messages ] }.
 * @param {Object|null} props.meta   Settings meta (null while loading).
 * @return {Object} { tabId: node }.
 */
export default function navMeta( { errors, meta } ) {
	const out = {};

	TABS.filter( ( tab ) => tab.isNew ).forEach( ( tab ) => {
		out[ tab.id ] = (
			<StatusBadge status="info">{ __( 'New', 'lw-pixel' ) }</StatusBadge>
		);
	} );

	if ( meta && ! meta.wooActive ) {
		out.woocommerce = (
			<StatusBadge status="idle">
				{ __( 'Inactive', 'lw-pixel' ) }
			</StatusBadge>
		);
	}

	Object.keys( errors ).forEach( ( key ) => {
		out[ tabOfField( key ) ] = (
			<span className="lw-admin-navflag">
				<Icon icon={ caution } size={ 18 } />
				<span className="screen-reader-text">
					{ __( 'Has invalid settings', 'lw-pixel' ) }
				</span>
			</span>
		);
	} );

	return out;
}

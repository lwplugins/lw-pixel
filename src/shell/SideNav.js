/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Icon,
	chevronDown,
	chevronRight,
	external,
	help,
} from '@wordpress/icons';
import { Badge } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import PixelMark from '../components/PixelMark';
import { DOCS_URL, VERSION } from '../data/boot';

/**
 * One nav link.
 *
 * @param {Object}  props
 * @param {Object}  props.tab       Tab.
 * @param {boolean} props.isCurrent Active.
 * @param {Element} props.meta      Optional node after the label.
 */
function NavItem( { tab, isCurrent, meta } ) {
	return (
		<li>
			<a
				href={ `#${ tab.id }` }
				className="lw-admin-sidenav__item"
				aria-current={ isCurrent ? 'page' : undefined }
			>
				<Icon icon={ tab.icon } size={ 20 } />
				<span className="lw-admin-sidenav__label">{ tab.label }</span>
				{ meta && (
					<span className="lw-admin-sidenav__meta">{ meta }</span>
				) }
				{ isCurrent && <Icon icon={ chevronRight } size={ 18 } /> }
			</a>
		</li>
	);
}

/**
 * Full-height sidebar: plugin header, grouped section links, docs link. On
 * mobile the list collapses behind a "current section" toggle.
 *
 * @param {Object} props
 * @param {Array}  props.tabs    Tab registry.
 * @param {Array}  props.groups  Nav groups ({ id, label }).
 * @param {string} props.current Active tab id.
 * @param {Object} props.meta    Optional node per tab id, after the label.
 * @param {string} props.docsUrl Documentation URL (server meta wins).
 */
export default function SideNav( {
	tabs,
	groups,
	current,
	meta = {},
	docsUrl,
} ) {
	const [ isOpen, setIsOpen ] = useState( false );
	const navId = useInstanceId( SideNav, 'lw-admin-sidenav' );
	const active = tabs.find( ( tab ) => tab.id === current );
	const item = ( tab ) => (
		<NavItem
			key={ tab.id }
			tab={ tab }
			isCurrent={ tab.id === current }
			meta={ meta[ tab.id ] }
		/>
	);

	useEffect( () => setIsOpen( false ), [ current ] );

	return (
		<aside className={ `lw-admin-sidebar ${ isOpen ? 'is-open' : '' }` }>
			<div className="lw-admin-sidebar__head">
				<a
					className="lw-admin-sidebar__home"
					href={ `#${ tabs[ 0 ].id }` }
					aria-label={ __( 'LW Pixel home', 'lw-pixel' ) }
				>
					<PixelMark />
					<strong>LW Pixel</strong>
				</a>
				<Badge intent="informational">{ `v${ VERSION }` }</Badge>
			</div>
			<button
				type="button"
				className="lw-admin-sidebar__toggle"
				aria-expanded={ isOpen }
				aria-controls={ navId }
				onClick={ () => setIsOpen( ! isOpen ) }
			>
				<Icon icon={ active.icon } size={ 20 } />
				<span>{ active.label }</span>
				{ meta[ active.id ] }
				<Icon icon={ chevronDown } size={ 20 } />
			</button>
			<nav
				id={ navId }
				className="lw-admin-sidenav"
				aria-label={ __( 'LW Pixel sections', 'lw-pixel' ) }
			>
				<ul>{ tabs.filter( ( tab ) => ! tab.group ).map( item ) }</ul>
				{ groups.map( ( group ) => (
					<div key={ group.id } className="lw-admin-sidenav__group">
						<h2
							className="lw-admin-sidenav__heading"
							id={ `${ navId }-${ group.id }` }
						>
							{ group.label }
						</h2>
						<ul aria-labelledby={ `${ navId }-${ group.id }` }>
							{ tabs
								.filter( ( tab ) => tab.group === group.id )
								.map( item ) }
						</ul>
					</div>
				) ) }
			</nav>
			<div className="lw-admin-sidebar__foot">
				<a
					className="lw-admin-sidenav__item"
					href={ docsUrl || DOCS_URL }
					target="_blank"
					rel="noopener noreferrer"
				>
					<Icon icon={ help } size={ 20 } />
					<span className="lw-admin-sidenav__label">
						{ __( 'Documentation', 'lw-pixel' ) }
					</span>
					<Icon icon={ external } size={ 16 } />
					<span className="screen-reader-text">
						{ __( '(opens in a new tab)', 'lw-pixel' ) }
					</span>
				</a>
			</div>
		</aside>
	);
}

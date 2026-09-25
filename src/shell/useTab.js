/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ALIASES } from './tabs';

const read = ( ids, fallback ) => {
	const raw = window.location.hash.replace( '#', '' );
	const hash = ALIASES[ raw ] || raw;
	if ( ids.includes( hash ) ) {
		return hash;
	}
	return ids.includes( fallback ) ? fallback : ids[ 0 ];
};

/**
 * Current tab from location.hash (classic hash slugs are mapped to their
 * new tab); the first load also honours a `?tab=` URL.
 *
 * @param {string[]} ids     Tab ids.
 * @param {string}   initial Tab from the URL (?tab=).
 * @return {string} Current tab id.
 */
export default function useTab( ids, initial ) {
	const [ tab, setTab ] = useState( () =>
		read( ids, ALIASES[ initial ] || initial )
	);

	useEffect( () => {
		const onChange = () => {
			setTab( read( ids, ids[ 0 ] ) );
			window.scrollTo( { top: 0 } );
		};
		window.addEventListener( 'hashchange', onChange );
		return () => window.removeEventListener( 'hashchange', onChange );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return tab;
}

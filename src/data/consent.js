/**
 * The consent mapping is stored as three pixel-ID lists (one per category);
 * the screen edits it as one category per pixel. Mirrors
 * includes/Consent/Manager.php: a later list wins, and a pixel missing from
 * every list falls back to its default category.
 */
export const CONSENT_LISTS = {
	marketing: 'consent_marketing_pixels',
	analytics: 'consent_analytics_pixels',
	functional: 'consent_unclassified_pixels',
};

/**
 * Effective category of a pixel ('' = never gated).
 *
 * @param {Object} options Draft options.
 * @param {Object} pixel   { id, defaultCategory }.
 * @return {string} Category.
 */
export function categoryOf( options, pixel ) {
	let found = '';
	Object.entries( CONSENT_LISTS ).forEach( ( [ category, key ] ) => {
		if ( ( options[ key ] || [] ).includes( pixel.id ) ) {
			found = category;
		}
	} );
	return found || pixel.defaultCategory;
}

/**
 * The three lists after moving one pixel into a category ('' = none).
 *
 * @param {Object} options  Draft options.
 * @param {string} id       Pixel ID.
 * @param {string} category Target category.
 * @return {Object} { list_key: ids } for all three lists.
 */
export function withCategory( options, id, category ) {
	return Object.fromEntries(
		Object.entries( CONSENT_LISTS ).map( ( [ cat, key ] ) => {
			const ids = ( options[ key ] || [] ).filter(
				( item ) => item !== id
			);
			return [ key, cat === category ? [ ...ids, id ] : ids ];
		} )
	);
}

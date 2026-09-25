/**
 * Whether a secret will be set after saving the draft: typed now, or saved
 * earlier (or pinned in wp-config.php) and not being removed.
 *
 * @param {Object} store Settings store.
 * @param {string} name  Secret option key.
 * @return {boolean} Set.
 */
export function hasSecret( store, name ) {
	const draft = store.data.options[ name ];
	if ( typeof draft === 'string' ) {
		return draft !== '';
	}
	return !! store.data.meta.secrets[ name ]?.set;
}

/**
 * Upper-case an ID as it is typed (G-, AW-, GTM- IDs are upper case).
 *
 * @param {string} value Typed value.
 * @return {string} Value.
 */
export const upper = ( value ) => value.toUpperCase();

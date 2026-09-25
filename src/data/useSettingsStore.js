/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { api, errorMessage, fieldErrors } from './api';

export const same = ( a, b ) => JSON.stringify( a ) === JSON.stringify( b );

/**
 * Only the options that differ from the server copy. Unsent keys keep their
 * stored value on the server (a switch that is not sent is never turned
 * off). Secrets are null on the server: null = keep, '' = remove, a string
 * replaces the stored one.
 *
 * @param {Object} server Server options.
 * @param {Object} draft  Draft options.
 * @return {Object} POST body.
 */
export const patchOf = ( server, draft ) =>
	Object.fromEntries(
		Object.keys( draft )
			.filter( ( key ) => ! same( draft[ key ], server[ key ] ) )
			.map( ( key ) => [ key, draft[ key ] ] )
	);

/**
 * Rebase the live draft onto a save response: start from the saved options
 * and keep every value the user changed after the save was sent (it differs
 * from the snapshot that was sent), so edits made mid-save are not lost.
 *
 * @param {Object} saved Saved options.
 * @param {Object} sent  Draft snapshot the save was built from.
 * @param {Object} live  Current draft.
 * @return {Object} Rebased draft.
 */
export function rebaseDraft( saved, sent, live ) {
	const next = { ...saved };

	Object.keys( live ).forEach( ( key ) => {
		if ( ! same( live[ key ], sent[ key ] ) ) {
			next[ key ] = live[ key ];
		}
	} );

	return next;
}

/**
 * The lw_pixel_options draft, saved atomically by one Save (top bar or
 * Cmd/Ctrl+S). A `400 lw_pixel_invalid` keeps the draft and puts
 * `data.fields` next to each field; the server saved nothing.
 *
 * @return {Object} Store: data { options, meta }, set, hasEdits, save, discard…
 */
export default function useSettingsStore() {
	const [ server, setServer ] = useState( null );
	const [ options, setOptions ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ errors, setErrors ] = useState( {} );
	const [ isSaving, setIsSaving ] = useState( false );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const apply = useCallback( ( data ) => {
		setServer( data );
		setOptions( data.options );
		setErrors( {} );
	}, [] );

	const reload = useCallback( () => {
		setError( null );
		return api
			.settings()
			.then( apply, ( e ) => setError( errorMessage( e ) ) );
	}, [ apply ] );

	useEffect( () => {
		reload();
	}, [ reload ] );

	const patch = options ? patchOf( server.options, options ) : {};
	const hasEdits = Object.keys( patch ).length > 0;

	const clearErrors = ( keys ) =>
		setErrors( ( prev ) => {
			if ( ! keys.some( ( key ) => key in prev ) ) {
				return prev;
			}
			const next = { ...prev };
			keys.forEach( ( key ) => delete next[ key ] );
			return next;
		} );

	const setMany = ( values ) => {
		setOptions( ( prev ) => ( { ...prev, ...values } ) );
		clearErrors( Object.keys( values ) );
	};

	const save = async () => {
		if ( ! hasEdits || isSaving ) {
			return false;
		}
		setIsSaving( true );
		const sent = options;
		let ok = false;
		try {
			const data = await api.saveSettings( patch );
			setServer( data );
			setOptions( ( live ) => rebaseDraft( data.options, sent, live ) );
			setErrors( {} );
			ok = true;
			createSuccessNotice( __( 'Settings saved.', 'lw-pixel' ), {
				type: 'snackbar',
			} );
		} catch ( e ) {
			const fields = fieldErrors( e );
			if ( fields ) {
				setErrors( fields );
			}
			createErrorNotice(
				fields
					? __(
							'Nothing was saved. Fix the highlighted fields and save again.',
							'lw-pixel'
						)
					: errorMessage( e ),
				{ type: 'snackbar' }
			);
		}
		setIsSaving( false );
		return ok;
	};

	return {
		data: options ? { options, meta: server.meta } : null,
		saved: server?.options || {},
		isLoading: ! options && ! error,
		error,
		reload,
		errors,
		has: ( key ) => !! options && key in options,
		isDirty: ( key ) => key in patch,
		set: ( key, value ) => setMany( { [ key ]: value } ),
		setMany,
		hasEdits,
		isSaving,
		discard: () => {
			setOptions( server.options );
			setErrors( {} );
		},
		save,
	};
}

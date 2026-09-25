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
import validateEvent from './validateEvent';
import { same } from './useSettingsStore';

/**
 * Editor fields of an event: the post title and state plus the meta record.
 *
 * @param {Object} event toEvent() result.
 * @return {Object} Flat fields.
 */
const fieldsOf = ( event ) => ( {
	title: event.title,
	enabled: event.enabled,
	...event.data,
} );

/**
 * Custom events: the list, one open editor (its own draft, saved by the top
 * bar Save or Cmd/Ctrl+S) and the row actions (switch on/off, delete). Every
 * write goes straight to the server; nothing here touches the settings draft.
 *
 * @return {Object} Store.
 */
export default function useCustomEvents() {
	const [ list, setList ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ editing, setEditing ] = useState( null );
	const [ errors, setErrors ] = useState( {} );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ busy, setBusy ] = useState( 0 );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const reload = useCallback( () => {
		setError( null );
		return api
			.customEvents()
			.then( setList, ( e ) => setError( errorMessage( e ) ) );
	}, [] );

	useEffect( () => {
		reload();
	}, [ reload ] );

	const replace = ( event ) =>
		setList( ( prev ) => {
			const exists = prev.events.some( ( item ) => item.id === event.id );
			return {
				...prev,
				events: exists
					? prev.events.map( ( item ) =>
							item.id === event.id ? event : item
						)
					: [ event, ...prev.events ],
			};
		} );

	const open = ( event ) => {
		const fields = event
			? fieldsOf( event )
			: { title: '', enabled: true, ...list.defaults };
		setEditing( {
			id: event ? event.id : 0,
			server: fields,
			draft: fields,
		} );
		setErrors( {} );
	};

	const patch = editing
		? Object.fromEntries(
				Object.keys( editing.draft )
					.filter(
						( key ) =>
							! editing.id ||
							! same(
								editing.draft[ key ],
								editing.server[ key ]
							)
					)
					.map( ( key ) => [ key, editing.draft[ key ] ] )
			)
		: {};
	const hasEdits =
		!! editing &&
		Object.keys( editing.draft ).some(
			( key ) => ! same( editing.draft[ key ], editing.server[ key ] )
		);

	const set = ( field, value ) => {
		setEditing( ( prev ) => ( {
			...prev,
			draft: { ...prev.draft, [ field ]: value },
		} ) );
		setErrors( ( prev ) => {
			if ( ! ( field in prev ) ) {
				return prev;
			}
			const next = { ...prev };
			delete next[ field ];
			return next;
		} );
	};

	const fail = ( e ) => {
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
	};

	const save = async () => {
		if ( ! editing || ! hasEdits || isSaving ) {
			return false;
		}
		const invalid = validateEvent( editing.draft );
		if ( Object.keys( invalid ).length ) {
			setErrors( invalid );
			createErrorNotice(
				__(
					'Nothing was saved. Fix the highlighted fields and save again.',
					'lw-pixel'
				),
				{ type: 'snackbar' }
			);
			return false;
		}
		setIsSaving( true );
		let ok = false;
		try {
			const event = editing.id
				? await api.updateEvent( editing.id, patch )
				: await api.createEvent( patch );
			replace( event );
			setEditing( {
				id: event.id,
				server: fieldsOf( event ),
				draft: fieldsOf( event ),
			} );
			setErrors( {} );
			ok = true;
			createSuccessNotice( __( 'Custom event saved.', 'lw-pixel' ), {
				type: 'snackbar',
			} );
		} catch ( e ) {
			fail( e );
		}
		setIsSaving( false );
		return ok;
	};

	const toggle = async ( event, enabled ) => {
		setBusy( event.id );
		try {
			replace( await api.updateEvent( event.id, { enabled } ) );
			createSuccessNotice(
				enabled
					? __( 'Custom event switched on.', 'lw-pixel' )
					: __( 'Custom event switched off.', 'lw-pixel' ),
				{ type: 'snackbar' }
			);
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
		setBusy( 0 );
	};

	const remove = async ( event ) => {
		setBusy( event.id );
		try {
			await api.deleteEvent( event.id );
			setList( ( prev ) => ( {
				...prev,
				events: prev.events.filter( ( item ) => item.id !== event.id ),
			} ) );
			setEditing( ( prev ) => ( prev?.id === event.id ? null : prev ) );
			createSuccessNotice( __( 'Custom event deleted.', 'lw-pixel' ), {
				type: 'snackbar',
			} );
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
		setBusy( 0 );
	};

	return {
		list,
		error,
		reload,
		isLoading: ! list && ! error,
		editing,
		errors,
		open,
		close: () => {
			setEditing( null );
			setErrors( {} );
		},
		set,
		hasEdits,
		isSaving,
		busy,
		save,
		discard: () => {
			setEditing( ( prev ) => ( { ...prev, draft: prev.server } ) );
			setErrors( {} );
		},
		toggle,
		remove,
	};
}

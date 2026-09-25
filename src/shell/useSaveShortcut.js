/**
 * WordPress dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { isKeyboardEvent } from '@wordpress/keycodes';

/**
 * Cmd/Ctrl+S saves (and never opens the browser's "Save page" dialog).
 *
 * Only listens on tabs that edit settings (`active`); elsewhere the browser
 * keeps its own shortcut. The latest callback and flag are read through a
 * ref, so the listener is added once per `active` change, not every render.
 *
 * @param {() => unknown} onSave  Save callback.
 * @param {boolean}       enabled Whether saving is possible right now.
 * @param {boolean}       active  Whether the current tab edits settings.
 */
export default function useSaveShortcut( onSave, enabled, active ) {
	const latest = useRef( { onSave, enabled } );

	useEffect( () => {
		latest.current = { onSave, enabled };
	} );

	useEffect( () => {
		if ( ! active ) {
			return;
		}
		const onKeyDown = ( event ) => {
			if ( ! isKeyboardEvent.primary( event, 's' ) ) {
				return;
			}
			event.preventDefault();
			if ( latest.current.enabled ) {
				latest.current.onSave();
			}
		};
		document.addEventListener( 'keydown', onKeyDown );
		return () => document.removeEventListener( 'keydown', onKeyDown );
	}, [ active ] );
}

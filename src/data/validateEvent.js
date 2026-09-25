/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Whether the browser accepts a CSS selector (the frontend runs it through
 * closest(), which throws on an invalid one).
 *
 * @param {string} selector Selector.
 * @return {boolean} Valid.
 */
export function isValidSelector( selector ) {
	try {
		document.createDocumentFragment().querySelector( selector );
		return true;
	} catch {
		return false;
	}
}

/**
 * Client-side checks of an event draft, mirroring the server's rules
 * (includes/Rest/Admin/CustomEvents/CustomEventInput.php) plus a real
 * selector parse the server cannot do.
 *
 * @param {Object} draft Editor fields.
 * @return {Object} { field: [ messages ] } (empty when valid).
 */
export default function validateEvent( draft ) {
	const errors = {};
	const add = ( field, message ) => ( errors[ field ] = [ message ] );

	if ( ! draft.event_name.trim() ) {
		add( 'event_name', __( 'Give the event a name.', 'lw-pixel' ) );
	} else if (
		! /^[A-Za-z][A-Za-z0-9_]{0,39}$/.test( draft.event_name.trim() )
	) {
		add(
			'event_name',
			__(
				'Start with a letter, then use letters, digits or underscores (max. 40), e.g. MyCustomEvent.',
				'lw-pixel'
			)
		);
	}

	if ( draft.trigger_type === 'click' ) {
		const selector = draft.selector.trim();
		if ( ! selector ) {
			add(
				'selector',
				__( 'A click trigger needs a CSS selector.', 'lw-pixel' )
			);
		} else if ( ! isValidSelector( selector ) ) {
			add(
				'selector',
				__( 'This is not a usable CSS selector.', 'lw-pixel' )
			);
		}
	}

	if (
		draft.page_pattern &&
		! /^[/*]\S*$/.test( draft.page_pattern.trim() )
	) {
		add(
			'page_pattern',
			__(
				'Start with / (a path such as /products/*) or leave it empty for every page.',
				'lw-pixel'
			)
		);
	}

	if ( draft.value && ! /^\d{1,9}(\.\d{1,4})?$/.test( draft.value.trim() ) ) {
		add(
			'value',
			__(
				'Use a number such as 10 or 9.99, or leave it empty.',
				'lw-pixel'
			)
		);
	}

	if ( ! /^[A-Z]{3}$/.test( draft.currency.trim().toUpperCase() ) ) {
		add(
			'currency',
			__(
				'Use a three-letter currency code such as HUF, EUR or USD.',
				'lw-pixel'
			)
		);
	}

	return errors;
}

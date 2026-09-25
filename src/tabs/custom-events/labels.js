/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Trigger choices, in the classic order.
 *
 * @return {Object[]} { value, label }.
 */
export const triggerOptions = () => [
	{ value: 'page_load', label: __( 'On page load', 'lw-pixel' ) },
	{ value: 'click', label: __( 'Click element (CSS selector)', 'lw-pixel' ) },
	{ value: 'scroll', label: __( 'Scroll percentage', 'lw-pixel' ) },
	{ value: 'time', label: __( 'Time on page', 'lw-pixel' ) },
];

/**
 * One-line description of when an event fires.
 *
 * @param {Object} data Event data.
 * @return {string} Summary.
 */
export function triggerSummary( data ) {
	switch ( data.trigger_type ) {
		case 'click':
			/* translators: %s: CSS selector. */
			return sprintf( __( 'Click on %s', 'lw-pixel' ), data.selector );
		case 'scroll':
			return sprintf(
				/* translators: %d: scroll depth in percent. */
				__( 'Scrolled to %d%%', 'lw-pixel' ),
				data.scroll_pct
			);
		case 'time':
			return sprintf(
				/* translators: %d: seconds. */
				__( 'After %d seconds on the page', 'lw-pixel' ),
				data.time_seconds
			);
		default:
			return __( 'On page load', 'lw-pixel' );
	}
}

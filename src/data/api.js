/**
 * Every REST call the admin makes, in one place (lw-pixel/v1, prefix
 * /admin). Responses go through ./shapes before the UI reads them, so a
 * backend shape change is a one-file fix there.
 */
/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NAMESPACE } from './boot';
import { toEvent, toEvents, toSettings } from './shapes';

const path = ( route ) => `/${ NAMESPACE }/admin${ route }`;
const post = ( route, data ) =>
	apiFetch( { path: path( route ), method: 'POST', data } );

export const api = {
	// GET → { options, meta }.
	settings: () =>
		apiFetch( { path: path( '/settings' ) } ).then( toSettings ),
	// POST any subset of options (atomic) → same shape.
	saveSettings: ( patch ) => post( '/settings', patch ).then( toSettings ),

	// Custom events (CPT lw_pixel_event).
	customEvents: () =>
		apiFetch( { path: path( '/custom-events' ) } ).then( toEvents ),
	createEvent: ( fields ) => post( '/custom-events', fields ).then( toEvent ),
	updateEvent: ( id, fields ) =>
		post( `/custom-events/${ id }`, fields ).then( toEvent ),
	deleteEvent: ( id ) =>
		apiFetch( {
			path: path( `/custom-events/${ id }` ),
			method: 'DELETE',
		} ),

	// Tools.
	migrators: () => apiFetch( { path: path( '/migrators' ) } ),
	runMigrator: ( id ) => post( `/migrators/${ id }/run`, {} ),
	systemReport: () => apiFetch( { path: path( '/system-report' ) } ),

	// ChatGPT Ads connection test (validate-only request).
	testChatgpt: () => post( '/test/chatgpt', {} ),
};

/**
 * Human message of a failed request.
 *
 * @param {Object} error apiFetch rejection.
 * @return {string} Message.
 */
export const errorMessage = ( error ) =>
	error?.message ||
	__(
		'That did not work. Please reload the page and try again.',
		'lw-pixel'
	);

/**
 * Per-field validation errors of a `400 lw_pixel_invalid` response.
 *
 * @param {Object} error apiFetch rejection.
 * @return {Object|null} { key: [ messages ] } or null.
 */
export function fieldErrors( error ) {
	const fields = error?.data?.fields;
	if ( ! fields || typeof fields !== 'object' ) {
		return null;
	}
	return Object.fromEntries(
		Object.entries( fields ).map( ( [ key, messages ] ) => [
			key,
			( Array.isArray( messages ) ? messages : [ messages ] ).map(
				String
			),
		] )
	);
}

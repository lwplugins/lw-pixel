/**
 * Response adapters: the ONLY place that knows the backend's field names
 * (lw-pixel/v1/admin/* — includes/Rest/Admin/*). The UI reads the objects
 * built here.
 */
/**
 * Internal dependencies
 */
import { DOCS_URL } from './boot';

const obj = ( value ) =>
	value && typeof value === 'object' && ! Array.isArray( value ) ? value : {};
const list = ( value ) => ( Array.isArray( value ) ? value : [] );
const str = ( value ) =>
	value === null || value === undefined ? '' : String( value );

/**
 * Secret state: { set, source: option|constant|none, hint }.
 *
 * @param {Object} data Raw state.
 * @return {Object} State.
 */
const toSecret = ( data ) => ( {
	set: !! data?.set,
	source: str( data?.source ) || 'none',
	hint: str( data?.hint ),
} );

/**
 * GET/POST /admin/settings → { options, meta }. Options stay keyed by the
 * option names (the store diffs them); secrets arrive as null.
 *
 * @param {Object} data Response.
 * @return {Object} Settings.
 */
export function toSettings( data ) {
	const meta = obj( data?.meta );

	return {
		options: { ...obj( data?.options ) },
		meta: {
			pixels: list( meta.pixels ).map( ( pixel ) => ( {
				id: str( pixel?.id ),
				label: str( pixel?.label ) || str( pixel?.id ),
				enabled: !! pixel?.enabled,
				configured: !! pixel?.configured,
				defaultCategory: str( pixel?.default_category ),
			} ) ),
			secrets: Object.fromEntries(
				Object.entries( obj( meta.secrets ) ).map(
					( [ key, value ] ) => [ key, toSecret( value ) ]
				)
			),
			locked: obj( meta.locked ),
			defaults: obj( meta.defaults ),
			integrations: obj( meta.integrations ),
			formsDetected: obj( meta.forms_detected ),
			wooActive: !! meta.woocommerce_active,
			lwCookieActive: !! meta.lw_cookie_active,
			canUnfilteredHtml: !! meta.can_unfiltered_html,
			lduModes: list( obj( meta.enums ).compliance_ldu_mode ).map( str ),
			maxCodeBytes: Number( meta.max_code_bytes ) || 65536,
			docsUrl: str( meta.docs_url ) || DOCS_URL,
		},
	};
}

/**
 * One custom event.
 *
 * @param {Object} data Raw event.
 * @return {Object} Event.
 */
export function toEvent( data ) {
	const fields = obj( data?.data );

	return {
		id: Number( data?.id ) || 0,
		title: str( data?.title ),
		enabled: !! data?.enabled,
		modified: str( data?.modified ),
		data: {
			event_name: str( fields.event_name ),
			trigger_type: str( fields.trigger_type ) || 'page_load',
			selector: str( fields.selector ),
			scroll_pct: Number( fields.scroll_pct ) || 50,
			time_seconds: Number( fields.time_seconds ) || 30,
			page_pattern: str( fields.page_pattern ),
			value: str( fields.value ),
			currency: str( fields.currency ) || 'USD',
			fire_once: !! fields.fire_once,
		},
	};
}

/**
 * GET /admin/custom-events → { events, runLimit }.
 *
 * @param {Object} data Response.
 * @return {Object} List.
 */
export function toEvents( data ) {
	const meta = obj( data?.meta );

	return {
		events: list( data?.events ).map( toEvent ),
		defaults: toEvent( { data: obj( meta.defaults ) } ).data,
		runLimit: Number( meta.run_limit ) || 100,
	};
}

/**
 * What the browser runtime hands to each provider on one page load.
 *
 * The page is served from a fake origin, every other request is blocked
 * (the vendor libraries never load), and the provider stubs' queues are
 * read back: exactly the calls the vendor library would process.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );

const RUNTIME = fs.readFileSync( path.join( __dirname, '../../assets/js/runtime.js' ), 'utf8' );
const ORIGIN = 'https://shop.example.test';

/** Frontend config of every provider with a page view. */
const PIXELS = {
	fb: { pixelId: '1234567890' },
	ga4: { measurementId: 'G-TEST123456', anonymizeIp: true, debug: false },
	tiktok: { pixelId: 'CTEST000000000000000' },
	pinterest: { tagId: '2613000000000' },
	reddit: { pixelId: 't2_test' },
	snapchat: { pixelId: '00000000-0000-0000-0000-000000000000' },
};

/** PageView as PayloadBuilder maps it (the providers' EVENT_MAPs). */
const PAGE_VIEW = {
	name: 'PageView',
	params: { page_title: 'Shop', page_location: 'https://cached.example.test/?fbclid=x' },
	mapped: {
		fb: { name: 'PageView', params: {} },
		ga4: { name: 'page_view', params: { page_title: 'Shop', page_location: 'https://cached.example.test/?fbclid=x' } },
		tiktok: { name: 'Pageview', params: {} },
		pinterest: { name: 'pagevisit', params: {} },
		reddit: { name: 'PageVisit', params: {} },
		snapchat: { name: 'PAGE_VIEW', params: {} },
	},
};

function payload( overrides ) {
	return Object.assign( { pixels: PIXELS, events: [ PAGE_VIEW ], consentClient: false, categories: {} }, overrides );
}

/**
 * Load a page carrying the payload and return the runtime's warnings.
 *
 * @param {import('@playwright/test').Page} page
 * @param {Object} data       Payload.
 * @param {string} headScript Inline script run before the runtime.
 */
async function load( page, data, headScript = '' ) {
	const warnings = [];
	page.on( 'console', ( msg ) => {
		if ( msg.text().indexOf( 'LW Pixel' ) !== -1 ) {
			warnings.push( msg.text() );
		}
	} );
	page.on( 'pageerror', ( err ) => warnings.push( 'pageerror: ' + err.message ) );

	const html = '<!doctype html><html><head>' +
		( headScript ? '<script>' + headScript + '</script>' : '' ) +
		'<script id="lw-pixel-data" type="application/json">' + JSON.stringify( data ) + '</script>' +
		'<script src="/runtime.js"></script></head><body><p>Shop</p></body></html>';

	await page.route( '**/*', ( route ) => {
		const url = route.request().url();
		if ( ! url.startsWith( ORIGIN ) ) {
			return route.abort();
		}
		return url.endsWith( '/runtime.js' )
			? route.fulfill( { body: RUNTIME, contentType: 'text/javascript' } )
			: route.fulfill( { body: html, contentType: 'text/html' } );
	} );
	await page.goto( ORIGIN + '/shop/?utm_source=test' );
	await page.waitForFunction( () => document.readyState === 'complete' );

	return warnings;
}

/** Every call queued per provider, as [command, first argument, second argument]. */
function queuedCalls( page ) {
	return page.evaluate( () => {
		const list = ( queue ) => Array.from( queue || [] ).map( ( args ) => Array.from( args ) );
		const tiktok = window.ttq ? list( window.ttq ) : [];
		Object.values( ( window.ttq && window.ttq._i ) || {} ).forEach( ( instance ) => tiktok.push( ...list( instance ) ) );
		return {
			fb: window.fbq ? list( window.fbq.queue ) : [],
			ga4: list( window.dataLayer ),
			tiktok,
			pinterest: window.pintrk ? list( window.pintrk.queue ) : [],
			reddit: window.rdt ? list( window.rdt.callQueue ) : [],
			snapchat: window.snaptr ? list( window.snaptr.queue ) : [],
		};
	} );
}

/** Page views each provider will record. */
function pageViews( calls ) {
	const count = ( list, test ) => list.filter( test ).length;
	return {
		fb: count( calls.fb, ( c ) => c[ 0 ] === 'track' && c[ 1 ] === 'PageView' ),
		// gtag('config') sends its own page_view unless send_page_view is false.
		ga4: count( calls.ga4, ( c ) => c[ 0 ] === 'event' && c[ 1 ] === 'page_view' ) +
			count( calls.ga4, ( c ) => c[ 0 ] === 'config' && ! ( c[ 2 ] && c[ 2 ].send_page_view === false ) ),
		tiktok: count( calls.tiktok, ( c ) => c[ 0 ] === 'page' || ( c[ 0 ] === 'track' && c[ 1 ] === 'Pageview' ) ),
		// pintrk('page') is the base call, not a conversion; pagevisit is.
		pinterest: count( calls.pinterest, ( c ) => c[ 0 ] === 'track' && c[ 1 ] === 'pagevisit' ),
		reddit: count( calls.reddit, ( c ) => c[ 0 ] === 'track' && c[ 1 ] === 'PageVisit' ),
		snapchat: count( calls.snapchat, ( c ) => c[ 0 ] === 'track' && c[ 1 ] === 'PAGE_VIEW' ),
	};
}

test( 'every provider initialises without an error', async ( { page } ) => {
	const warnings = await load( page, payload() );
	const calls = await queuedCalls( page );

	expect( warnings ).toEqual( [] );
	expect( calls.fb ).toContainEqual( [ 'init', '1234567890' ] );
	expect( calls.ga4.map( ( c ) => c[ 0 ] ) ).toContain( 'config' );
	expect( calls.pinterest ).toContainEqual( [ 'load', '2613000000000' ] );
	expect( calls.reddit ).toContainEqual( [ 'init', 't2_test' ] );
	expect( calls.snapchat.map( ( c ) => c[ 0 ] ) ).toContain( 'init' );
	expect( await page.evaluate( () => Object.keys( window.ttq._i || {} ) ) ).toEqual( [ 'CTEST000000000000000' ] );
} );

test( 'one page load is one page view for every provider', async ( { page } ) => {
	await load( page, payload() );

	expect( pageViews( await queuedCalls( page ) ) ).toEqual( {
		fb: 1, ga4: 1, tiktok: 1, pinterest: 1, reddit: 1, snapchat: 1,
	} );
} );

test( 'no page view is sent when the PageView event is switched off', async ( { page } ) => {
	await load( page, payload( { events: [] } ) );

	expect( pageViews( await queuedCalls( page ) ) ).toEqual( {
		fb: 0, ga4: 0, tiktok: 0, pinterest: 0, reddit: 0, snapchat: 0,
	} );
} );

test( 'page_location is the visitor\'s own URL, not the cached one', async ( { page } ) => {
	await load( page, payload() );
	const calls = await queuedCalls( page );
	const ga4PageView = calls.ga4.find( ( c ) => c[ 0 ] === 'event' && c[ 1 ] === 'page_view' );

	expect( ga4PageView[ 2 ].page_location ).toBe( ORIGIN + '/shop/?utm_source=test' );
} );

test( 'GA4 queues the js command once when another script defined gtag first', async ( { page } ) => {
	const consentDefaults = 'window.dataLayer=window.dataLayer||[];' +
		'function gtag(){dataLayer.push(arguments);}gtag("consent","default",{ad_storage:"denied"});';
	await load( page, payload( { pixels: { ga4: PIXELS.ga4 } } ), consentDefaults );
	const commands = ( await queuedCalls( page ) ).ga4.map( ( c ) => c[ 0 ] );

	expect( commands.filter( ( c ) => c === 'js' ) ).toHaveLength( 1 );
	expect( commands.indexOf( 'js' ) ).toBeLessThan( commands.indexOf( 'config' ) );
} );

test( 'a marketing pixel waits for consent, then sends its page view once', async ( { page } ) => {
	const gated = payload( {
		pixels: { reddit: PIXELS.reddit, ga4: PIXELS.ga4 },
		consentClient: true,
		categories: { reddit: 'marketing', ga4: 'analytics' },
	} );
	await load( page, gated );

	expect( await page.evaluate( () => typeof window.rdt ) ).toBe( 'undefined' );

	await page.evaluate( () => {
		const consent = { categories: { necessary: true, analytics: true, marketing: true } };
		document.cookie = 'lw_cookie_consent=' + encodeURIComponent( btoa( JSON.stringify( consent ) ) ) + '; path=/';
		window.dispatchEvent( new Event( 'lwCookieConsent' ) );
	} );
	const views = pageViews( await queuedCalls( page ) );

	expect( [ views.reddit, views.ga4 ] ).toEqual( [ 1, 1 ] );
} );

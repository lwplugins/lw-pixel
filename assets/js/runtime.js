/**
 * LW Pixel — frontend runtime.
 *
 * Reads the inline JSON payload printed in <head>, loads the configured
 * pixel libraries, and dispatches queued events to each provider.
 */
(function () {
	'use strict';

	var DATA_NODE_ID = 'lw-pixel-data';
	var payload      = null;
	var initialized  = {};

	/**
	 * Read the visitor's consent categories from the lw-cookie cookie.
	 * Cache-safe: this runs per-visitor in the browser, so full-page caching
	 * cannot bake one visitor's consent into the HTML served to another.
	 */
	function getConsentCategories() {
		try {
			var match = document.cookie.match( /(?:^|;\s*)lw_cookie_consent=([^;]+)/ );
			if ( ! match) {
				return { necessary: true };
			}
			var data = JSON.parse( decodeURIComponent( match[1] ) );
			return (data && data.categories) ? data.categories : { necessary: true };
		} catch (e) {
			return { necessary: true };
		}
	}

	/**
	 * Whether a pixel may fire for the current visitor.
	 * When the payload was built without client-side gating, the server already
	 * filtered — allow everything present. Otherwise gate by the pixel's
	 * consent category (uncategorized pixels are always allowed).
	 */
	function pixelAllowed(id) {
		if ( ! payload || ! payload.consentClient) {
			return true;
		}
		var category = (payload.categories || {})[id];
		if ( ! category) {
			return true;
		}
		return getConsentCategories()[category] === true;
	}

	function readPayload() {
		var node = document.getElementById( DATA_NODE_ID );
		if ( ! node) {
			return null;
		}
		try {
			return JSON.parse( node.textContent || '{}' );
		} catch (e) {
			return null;
		}
	}

	function loadScript(src, attrs) {
		var s   = document.createElement( 'script' );
		s.src   = src;
		s.async = true;
		if (attrs) {
			Object.keys( attrs ).forEach(
				function (k) {
					s.setAttribute( k, attrs[k] ); }
			);
		}
		document.head.appendChild( s );
		return s;
	}

	function ensureGtag() {
		window.dataLayer = window.dataLayer || [];
		if (typeof window.gtag !== 'function') {
			window.gtag = function () {
				window.dataLayer.push( arguments ); };
			window.gtag( 'js', new Date() );
		}
	}

	/**
	 * Merge server-mapped params with the runtime params captured at fire time.
	 */
	function mergeParams(mapped, params) {
		var base = (mapped && mapped.params) || {};
		var out  = {};
		Object.keys( base ).forEach( function (k) { out[k] = base[k]; } );
		Object.keys( params || {} ).forEach( function (k) { out[k] = params[k]; } );
		return out;
	}

	var providers = {

		fb: {
			loaded: false,
			init: function (config) {
				if ( ! config.pixelId || this.loaded) {
					return;
				}
				/* eslint-disable */
				! function (f,b,e,v,n,t,s) {
					if (f.fbq) {
						return;
					}n = f.fbq = function () {
						n.callMethod ? n.callMethod.apply( n,arguments ) : n.queue.push( arguments )};if ( ! f._fbq) {
						f._fbq = n;
						}n.push = n;n.loaded = ! 0;n.version = '2.0';n.queue = [];t = b.createElement( e );t.async = ! 0;t.src = v;s = b.getElementsByTagName( e )[0];s.parentNode.insertBefore( t,s )}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
				/* eslint-enable */
				window.fbq( 'init', config.pixelId );
				this.loaded = true;
			},
			fire: function (mapped, params) {
				if ( ! window.fbq || ! mapped) {
					return;
				}
				window.fbq( mapped.type || 'track', mapped.name, mergeParams( mapped, params ) );
			}
		},

		ga4: {
			init: function (config) {
				if ( ! config.measurementId) {
					return;
				}
				ensureGtag();
				loadScript( 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent( config.measurementId ) );
				window.gtag(
					'config',
					config.measurementId,
					{
						anonymize_ip: ! ! config.anonymizeIp,
						debug_mode: ! ! config.debug
					}
				);
			},
			fire: function (mapped, params) {
				if ( ! window.gtag || ! mapped) {
					return;
				}
				window.gtag( 'event', mapped.name, mergeParams( mapped, params ) );
			}
		},

		gads: {
			init: function (config) {
				if ( ! config.conversionId) {
					return;
				}
				ensureGtag();
				loadScript( 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent( config.conversionId ) );
				window.gtag( 'config', config.conversionId );
			},
			/**
			 * Unlike every other provider, `mapped` here IS the flat gtag conversion
			 * payload (send_to, value, currency, transaction_id) — not a
			 * {name, params} pair. mergeParams() reads mapped.params, which does not
			 * exist for this provider, so merging would silently drop send_to and
			 * transaction_id. There are also no runtime-captured params to merge for
			 * this provider: map_event() only returns non-null for Purchase, and its
			 * params are already baked in server-side. Pass mapped straight through.
			 */
			fire: function (mapped) {
				if ( ! window.gtag || ! mapped) {
					return;
				}
				window.gtag( 'event', 'conversion', mapped );
			}
		},

		gtm: {
			loaded: false,
			init: function (config) {
				if ( ! config.containerId) {
					return;
				}
				window.dataLayer = window.dataLayer || [];
				window.dataLayer.push( { 'gtm.start': new Date().getTime(), event: 'gtm.js' } );
				if (config.dataLayerOnly || this.loaded) {
					this.loaded = true;
					return;
				}
				loadScript( 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent( config.containerId ) );
				this.loaded = true;
			},
			fire: function (mapped, params) {
				window.dataLayer = window.dataLayer || [];
				window.dataLayer.push( Object.assign( { event: mapped.event }, params || {} ) );
			}
		},

		tiktok: {
			init: function (config) {
				if ( ! config.pixelId) {
					return;
				}
				/* eslint-disable */
				! function (w,d,t) {
					w.TiktokAnalyticsObject = t;var ttq = w[t] = w[t] || [];ttq.methods = ["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"];ttq.setAndDefer = function (t,e) {
						t[e] = function () {
							t.push( [e].concat( Array.prototype.slice.call( arguments,0 ) ) )}};for (var i = 0;i < ttq.methods.length;i++) {
										ttq.setAndDefer( ttq,ttq.methods[i] );
							}ttq.instance           = function (t) {
								for (var e = ttq._i[t] || [],n = 0;n < ttq.methods.length;n++) {
									ttq.setAndDefer( e,ttq.methods[n] );
								}return e};ttq.load = function (e,n) {
									var r = "https://analytics.tiktok.com/i18n/pixel/events.js",o = n && n.partner;ttq._i = ttq._i || {};ttq._i[e] = [];ttq._i[e]._u = r;ttq._t = ttq._t || {};ttq._t[e] = +new Date;ttq._o = ttq._o || {};ttq._o[e] = n || {};var i = document.createElement( "script" );i.type = "text/javascript";i.async = ! 0;i.src = r + "?sdkid=" + e + "&lib=" + t;var a = document.getElementsByTagName( "script" )[0];a.parentNode.insertBefore( i,a )};ttq.load( arguments[0] );ttq.page()}(window,document,'ttq');
				/* eslint-enable */
				window.ttq.load( config.pixelId );
				window.ttq.page();
			},
			fire: function (mapped, params) {
				if ( ! window.ttq || ! mapped) {
					return;
				}
				window.ttq.track( mapped.name, mergeParams( mapped, params ) );
			}
		},

		pinterest: {
			init: function (config) {
				if ( ! config.tagId) {
					return;
				}
				/* eslint-disable */
				! function (e) {
					if ( ! window.pintrk) {
						window.pintrk = function () {
							window.pintrk.queue.push( Array.prototype.slice.call( arguments ) );};var n = window.pintrk;n.queue = [],n.version = "3.0";var t = document.createElement( "script" );t.async = ! 0,t.src = e;var r = document.getElementsByTagName( "script" )[0];r.parentNode.insertBefore( t,r )}}('https://s.pinimg.com/ct/core.js');
				/* eslint-enable */
				window.pintrk( 'load', config.tagId );
				window.pintrk( 'page' );
			},
			fire: function (mapped, params) {
				if ( ! window.pintrk || ! mapped) {
					return;
				}
				window.pintrk( 'track', mapped.name, mergeParams( mapped, params ) );
			}
		},

		bing: {
			init: function (config) {
				if ( ! config.tagId) {
					return;
				}
				/* eslint-disable */
				(function (w,d,t,r,u) {
					var f,n,i;w[u] = w[u] || [],f = function () {
						var o = {ti:config.tagId};o.q = w[u],w[u] = new UET( o ),w[u].push( "pageLoad" )},n = d.createElement( t ),n.src = r,n.async = 1,n.onload = n.onreadystatechange = function () {
							var s = this.readyState;s && s !== "loaded" && s !== "complete" || (f(),n.onload = n.onreadystatechange = null)},i = d.getElementsByTagName( t )[0],i.parentNode.insertBefore( n,i )})( window,document,"script","//bat.bing.com/bat.js","uetq" );
				/* eslint-enable */
			},
			fire: function (mapped, params) {
				if ( ! window.uetq || ! mapped) {
					return;
				}
				window.uetq.push( 'event', mapped.name, mergeParams( mapped, params ) );
			}
		},

		reddit: {
			init: function (config) {
				if ( ! config.pixelId) {
					return;
				}
				/* eslint-disable */
				!function (w,d) {if ( ! w.rdt) {var p = w.rdt = function () {p.sendEvent ? p.sendEvent.apply( p,arguments ) : p.callQueue.push( arguments )};p.callQueue = [];var t = d.createElement( "script" );t.src = "https://www.redditstatic.com/ads/pixel.js",t.async = ! 0;var s = d.getElementsByTagName( "script" )[0];s.parentNode.insertBefore( t,s )}}(window,document);
				/* eslint-enable */
				window.rdt( 'init', config.pixelId );
				window.rdt( 'track', 'PageVisit' );
			},
			fire: function (mapped, params) {
				if ( ! window.rdt || ! mapped) {
					return;
				}
				window.rdt( 'track', mapped.name, mergeParams( mapped, params ) );
			}
		},

		snapchat: {
			init: function (config) {
				if ( ! config.pixelId) {
					return;
				}
				/* eslint-disable */
				(function (e,t,n) {if (e.snaptr) return;var a = e.snaptr = function () {a.handleRequest ? a.handleRequest.apply( a,arguments ) : a.queue.push( arguments )};a.queue = [];var s = 'script';r = t.createElement( s );r.async = ! 0;r.src = n;var u = t.getElementsByTagName( s )[0];u.parentNode.insertBefore( r,u )})( window,document,'https://sc-static.net/scevent.min.js' );
				/* eslint-enable */
				window.snaptr( 'init', config.pixelId, { user_email: config.email || '' } );
				window.snaptr( 'track', 'PAGE_VIEW' );
			},
			fire: function (mapped, params) {
				if ( ! window.snaptr || ! mapped) {
					return;
				}
				window.snaptr( 'track', mapped.name, mergeParams( mapped, params ) );
			}
		},

		x: {
			init: function (config) {
				if ( ! config.pixelId) {
					return;
				}
				/* eslint-disable */
				!function (e,t,n,s,u,a) {e.twq || (s = e.twq = function () {s.exe ? s.exe.apply( s,arguments ) : s.queue.push( arguments );},s.version = '1.1',s.queue = [],u = t.createElement( n ),u.async = ! 0,u.src = 'https://static.ads-twitter.com/uwt.js',a = t.getElementsByTagName( n )[0],a.parentNode.insertBefore( u,a ))}(window,document,'script');
				/* eslint-enable */
				window.twq( 'config', config.pixelId );
			},
			fire: function (mapped, params) {
				if ( ! window.twq || ! mapped) {
					return;
				}
				window.twq( 'event', mapped.name, mergeParams( mapped, params ) );
			}
		}
	};

	/**
	 * Initialise every configured pixel the visitor currently allows and has
	 * not been initialised yet. Returns the ids initialised on this pass so the
	 * caller can fire their base/pending events.
	 */
	function initAllowedPixels() {
		var newly = [];

		Object.keys( payload.pixels ).forEach(
			function (id) {
				if (initialized[id] || ! pixelAllowed( id )) {
					return;
				}
				var provider = providers[id];
				if ( ! provider) {
					return;
				}
				try {
					provider.init( payload.pixels[id] );
					initialized[id] = true;
					newly.push( id );
				} catch (e) { /* noop */ }
			}
		);

		return newly;
	}

	/**
	 * Fire the queued page-load and pending events, but only for the given
	 * (just-initialised) pixels — so a pixel that gains consent later still
	 * receives its PageView.
	 */
	function fireQueuedEventsFor(ids) {
		var allow = {};
		ids.forEach( function (id) { allow[id] = true; } );

		var lists = [ payload.events || [] ];
		var auto  = payload.auto_events || {};
		lists.push( auto.pending || [] );

		lists.forEach(
			function (list) {
				list.forEach(
					function (entry) {
						Object.keys( entry.mapped || {} ).forEach(
							function (pixelId) {
								if ( ! allow[pixelId]) { return; }
								var provider = providers[pixelId];
								if ( ! provider || typeof provider.fire !== 'function') { return; }
								try { provider.fire( entry.mapped[pixelId], entry.params || {} ); } catch (e) { /* noop */ }
							}
						);
					}
				);
			}
		);
	}

	function init() {
		payload = readPayload();
		if ( ! payload || ! payload.pixels) {
			return;
		}

		fireQueuedEventsFor( initAllowedPixels() );

		(payload.custom_events || []).forEach(
			function (cev) {
				scheduleCustomEvent( cev );
			}
		);

		var auto = payload.auto_events || {};
		setupAutoScroll( auto.scroll );
		setupAutoTime( auto.time );
		setupAutoDownload( auto.download );
		setupAutoContactClick( auto.phone, 'tel:', 'phone' );
		setupAutoContactClick( auto.email, 'mailto:', 'email' );

		// Re-evaluate on a consent change (lw-cookie fires this): initialise any
		// newly-allowed pixel and fire its base/pending events. Auto-event
		// listeners below gate on `initialized` at fire time, so they start
		// working for the pixel automatically.
		window.addEventListener(
			'lwCookieConsent',
			function () {
				var newly = initAllowedPixels();
				if (newly.length) {
					fireQueuedEventsFor( newly );
				}
			}
		);
	}

	function fireMapped(mapped, params) {
		Object.keys( mapped || {} ).forEach(
			function (pixelId) {
				if ( ! initialized[pixelId]) { return; }
				var provider = providers[pixelId];
				if ( ! provider || typeof provider.fire !== 'function') { return; }
				try { provider.fire( mapped[pixelId], params || {} ); } catch (e) { /* noop */ }
			}
		);
	}

	function setupAutoScroll(cfg) {
		if ( ! cfg || ! cfg.enabled || ! cfg.thresholds || ! cfg.thresholds.length) { return; }
		var fired   = {};
		var handler = function () {
			var doc    = document.documentElement;
			var scroll = (window.scrollY || doc.scrollTop) + window.innerHeight;
			var height = doc.scrollHeight || document.body.scrollHeight;
			if (height <= 0) { return; }
			var pct = Math.round( (scroll / height) * 100 );
			cfg.thresholds.forEach(
				function (t) {
					if (pct >= t && ! fired[t]) {
						fired[t] = true;
						fireMapped( cfg.mapped, { scroll_depth: t } );
					}
				}
			);
		};
		window.addEventListener( 'scroll', handler, { passive: true } );
	}

	function setupAutoTime(cfg) {
		if ( ! cfg || ! cfg.enabled || ! cfg.thresholds) { return; }
		cfg.thresholds.forEach(
			function (seconds) {
				setTimeout(
					function () { fireMapped( cfg.mapped, { time_seconds: seconds } ); },
					Math.max( 1, seconds | 0 ) * 1000
				);
			}
		);
	}

	function setupAutoDownload(cfg) {
		if ( ! cfg || ! cfg.enabled || ! cfg.extensions || ! cfg.extensions.length) { return; }
		document.addEventListener(
			'click',
			function (e) {
				var link = e.target.closest && e.target.closest( 'a[href]' );
				if ( ! link) { return; }
				var href = link.getAttribute( 'href' ) || '';
				var ext  = (href.split( '?' )[0].split( '#' )[0].split( '.' ).pop() || '').toLowerCase();
				if (cfg.extensions.indexOf( ext ) === -1) { return; }
				fireMapped( cfg.mapped, { file_url: href, file_extension: ext } );
			}
		);
	}

	/**
	 * Fire a Contact event when a visitor clicks a tel:/mailto: link.
	 *
	 * @param {Object} cfg    Auto-event config block ({enabled, mapped}).
	 * @param {string} scheme Link scheme to match, e.g. 'tel:'.
	 * @param {string} method Value reported as contact_method.
	 */
	function setupAutoContactClick(cfg, scheme, method) {
		if ( ! cfg || ! cfg.enabled) { return; }
		document.addEventListener(
			'click',
			function (e) {
				// The " i" flag matters: TEL:/Mailto: are valid, and would not match otherwise.
				var link = e.target.closest && e.target.closest( 'a[href^="' + scheme + '" i]' );
				if ( ! link) { return; }
				var href = link.getAttribute( 'href' ) || '';
				fireMapped(
					cfg.mapped,
					{ contact_method: method, contact_target: href.slice( scheme.length ) }
				);
			}
		);
	}

	function fireCustomEvent(cev) {
		Object.keys( cev.mapped || {} ).forEach(
			function (pixelId) {
				if ( ! initialized[pixelId]) { return; }
				var provider = providers[pixelId];
				if ( ! provider || typeof provider.fire !== 'function') {
					return;
				}
				try { provider.fire( cev.mapped[pixelId], cev.params || {} ); } catch (e) { /* noop */ }
			}
		);

		if (cev.fire_once) {
			markFired( cev.id );
		}
	}

	function alreadyFired(id) {
		try {
			return window.sessionStorage.getItem( 'lw_pixel_ce_' + id ) === '1';
		} catch (e) { return false; }
	}

	function markFired(id) {
		try { window.sessionStorage.setItem( 'lw_pixel_ce_' + id, '1' ); } catch (e) { /* noop */ }
	}

	function scheduleCustomEvent(cev) {
		if (cev.fire_once && alreadyFired( cev.id )) {
			return;
		}

		switch (cev.trigger_type) {
			case 'page_load':
				fireCustomEvent( cev );
				return;
			case 'click':
				if ( ! cev.selector) { return; }
				document.addEventListener(
					'click',
					function (e) {
						var target = e.target.closest && e.target.closest( cev.selector );
						if (target) { fireCustomEvent( cev ); }
					}
				);
				return;
			case 'scroll':
				attachScrollTrigger( cev );
				return;
			case 'time':
				setTimeout( function () { fireCustomEvent( cev ); }, Math.max( 1, cev.time_seconds | 0 ) * 1000 );
				return;
		}
	}

	function attachScrollTrigger(cev) {
		var fired   = false;
		var pct     = Math.max( 1, Math.min( 100, cev.scroll_pct | 0 ) );
		var handler = function () {
			if (fired) { return; }
			var doc    = document.documentElement;
			var scroll = (window.scrollY || doc.scrollTop) + window.innerHeight;
			var height = doc.scrollHeight || document.body.scrollHeight;
			if (height <= 0) { return; }
			var reached = (scroll / height) * 100;
			if (reached >= pct) {
				fired = true;
				fireCustomEvent( cev );
				window.removeEventListener( 'scroll', handler );
			}
		};
		window.addEventListener( 'scroll', handler, { passive: true } );
	}

	if (document.readyState === 'loading') {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
